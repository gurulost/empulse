<template>
    <div class="logic-editor">
        <div class="mb-3">
            <label class="form-label text-secondary small fw-bold text-uppercase">Rule Matching</label>
            <select class="form-select form-select-sm" v-model="state.operator" :disabled="disabled">
                <option value="and">All rules must match (AND)</option>
                <option value="or">Any rule can match (OR)</option>
            </select>
            <div class="form-text">
                Imported logic metadata is preserved automatically when you save.
            </div>
        </div>

        <div v-if="state.rules.length === 0" class="text-center p-3 bg-light rounded mb-3">
            <small class="text-muted">No display logic defined. This question will always be shown.</small>
        </div>

        <div v-else class="list-group mb-3">
            <div v-for="(rule, idx) in state.rules" :key="idx" class="list-group-item bg-light">
                <div class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label small text-muted fw-semibold">Question ID</label>
                        <input
                            type="text"
                            class="form-control form-control-sm"
                            v-model="rule.qid"
                            placeholder="Question ID (e.g. Q1)"
                            :disabled="disabled"
                        >
                    </div>

                    <div class="col-md-7">
                        <label class="form-label small text-muted fw-semibold">Match Any Of These Values</label>
                        <input
                            type="text"
                            class="form-control form-control-sm"
                            v-model="rule.valuesText"
                            placeholder="Comma-separated values"
                            :disabled="disabled"
                        >
                        <div class="form-text">Use commas to preserve multiple `equals_any` values.</div>
                    </div>

                    <div class="col-md-1">
                        <button class="btn btn-outline-danger btn-sm w-100" @click="removeRule(idx)" :disabled="disabled">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <button class="btn btn-sm btn-outline-primary w-100" @click="addRule" :disabled="disabled">
            <i class="bi bi-plus-circle me-1"></i> Add Logic Rule
        </button>
    </div>
</template>

<script setup>
import { reactive, watch } from 'vue';

const props = defineProps({
    modelValue: {
        type: [Object, Array, null],
        default: null,
    },
    disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const clone = (value) => JSON.parse(JSON.stringify(value ?? null));

const omitKeys = (value, keys) => {
    const output = {};

    Object.entries(value ?? {}).forEach(([key, entryValue]) => {
        if (!keys.includes(key)) {
            output[key] = clone(entryValue);
        }
    });

    return output;
};

const defaultState = () => ({
    shape: 'object',
    combinatorKey: 'operator',
    operator: 'and',
    extras: {},
    rules: [],
});

const emptyRule = () => ({
    qid: '',
    valuesText: '',
    extras: {},
});

const state = reactive(defaultState());

const parseCondition = (condition = {}) => ({
    qid: condition.qid ?? '',
    valuesText: Array.isArray(condition.equals_any)
        ? condition.equals_any.map((value) => String(value)).join(', ')
        : (condition.equals_any !== undefined && condition.equals_any !== null ? String(condition.equals_any) : ''),
    extras: omitKeys(condition, ['qid', 'equals_any']),
});

const parseLogic = (logic) => {
    if (!logic || (Array.isArray(logic) && logic.length === 0)) {
        return defaultState();
    }

    if (Array.isArray(logic)) {
        return {
            shape: 'array',
            combinatorKey: 'operator',
            operator: 'and',
            extras: {},
            rules: logic.map(parseCondition),
        };
    }

    const combinatorKey = ['operator', 'combinator', 'mode'].find((key) => Object.prototype.hasOwnProperty.call(logic, key)) ?? 'operator';

    return {
        shape: 'object',
        combinatorKey,
        operator: String(logic[combinatorKey] ?? 'and').toLowerCase(),
        extras: omitKeys(logic, ['when', 'operator', 'combinator', 'mode']),
        rules: Array.isArray(logic.when) ? logic.when.map(parseCondition) : [],
    };
};

const normalizeValues = (valuesText) => valuesText
    .split(',')
    .map((value) => value.trim())
    .filter((value) => value !== '');

const formattedLogic = () => {
    const rules = state.rules
        .map((rule) => ({
            ...clone(rule.extras),
            qid: rule.qid.trim(),
            equals_any: normalizeValues(rule.valuesText),
        }))
        .filter((rule) => rule.qid !== '' || rule.equals_any.length > 0 || Object.keys(rule.extras).length > 0)
        .map((rule) => ({
            ...rule,
            equals_any: rule.equals_any,
        }));

    if (rules.length === 0) {
        return null;
    }

    if (state.shape === 'array' && state.operator === 'and' && Object.keys(state.extras).length === 0) {
        return rules;
    }

    return {
        ...clone(state.extras),
        [state.combinatorKey || 'operator']: state.operator,
        when: rules,
    };
};

const syncFromModel = (logic) => {
    const parsed = parseLogic(logic);

    state.shape = parsed.shape;
    state.combinatorKey = parsed.combinatorKey;
    state.operator = parsed.operator;
    state.extras = parsed.extras;
    state.rules = parsed.rules;
};

const addRule = () => {
    if (props.disabled) {
        return;
    }

    state.rules.push(emptyRule());
};

const removeRule = (idx) => {
    if (props.disabled) {
        return;
    }

    state.rules.splice(idx, 1);
};

watch(
    () => props.modelValue,
    (newVal) => {
        const nextJson = JSON.stringify(clone(newVal));
        const currentJson = JSON.stringify(formattedLogic());

        if (nextJson !== currentJson) {
            syncFromModel(newVal);
        }
    },
    { immediate: true, deep: true }
);

watch(
    state,
    () => {
        emit('update:modelValue', formattedLogic());
    },
    { deep: true }
);
</script>
