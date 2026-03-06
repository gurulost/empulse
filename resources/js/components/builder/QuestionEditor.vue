<template>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 pt-4 px-4 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-1 fw-bold">Edit Question</h5>
                <span class="badge bg-light text-secondary border rounded-pill fw-normal font-monospace">{{ item.qid }}</span>
            </div>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3">{{ localItem.type }}</span>
        </div>
        <div class="card-body p-4">
            <div class="mb-4">
                <label class="form-label text-secondary small fw-bold text-uppercase">Question Text</label>
                <input
                    type="text"
                    class="form-control form-control-lg"
                    v-model="localItem.question"
                    placeholder="Enter your question here..."
                    :disabled="readOnly"
                >
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <label class="form-label text-secondary small fw-bold text-uppercase">Type</label>
                    <select class="form-select" v-model="localItem.type" :disabled="readOnly">
                        <option value="slider">Slider (Scale)</option>
                        <option value="text">Text Input</option>
                        <option value="text_short">Short Text</option>
                        <option value="text_long">Long Text</option>
                        <option value="number_integer">Number (Integer)</option>
                        <option value="dropdown">Dropdown</option>
                        <option value="single_select">Single Select</option>
                        <option value="single_select_text">Single Select + Other</option>
                        <option value="multi_select">Multi Select</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-secondary small fw-bold text-uppercase">QID</label>
                    <input type="text" class="form-control bg-light" v-model="localItem.qid" disabled>
                </div>
            </div>

            <div v-if="localItem.type === 'slider'" class="mb-4 p-4 rounded bg-light border-0">
                <h6 class="mb-3 fw-bold text-secondary">Scale Configuration</h6>
                <div class="row g-3">
                    <div class="col-4">
                        <label class="small text-muted fw-semibold">Min</label>
                        <input type="number" class="form-control form-control-sm" v-model.number="localItem.scale_config.min" :disabled="readOnly">
                    </div>
                    <div class="col-4">
                        <label class="small text-muted fw-semibold">Max</label>
                        <input type="number" class="form-control form-control-sm" v-model.number="localItem.scale_config.max" :disabled="readOnly">
                    </div>
                    <div class="col-4">
                        <label class="small text-muted fw-semibold">Step</label>
                        <input type="number" class="form-control form-control-sm" v-model.number="localItem.scale_config.step" :disabled="readOnly">
                    </div>
                    <div class="col-6">
                        <label class="small text-muted fw-semibold">Left Label</label>
                        <input type="text" class="form-control form-control-sm" v-model="localItem.scale_config.left_label" placeholder="e.g. Disagree" :disabled="readOnly">
                    </div>
                    <div class="col-6">
                        <label class="small text-muted fw-semibold">Right Label</label>
                        <input type="text" class="form-control form-control-sm" v-model="localItem.scale_config.right_label" placeholder="e.g. Agree" :disabled="readOnly">
                    </div>
                </div>
            </div>

            <div v-if="supportsOptions" class="mb-4 p-4 rounded bg-light border-0">
                <h6 class="mb-3 d-flex justify-content-between align-items-center fw-bold text-secondary">
                    Options
                    <button class="btn btn-sm btn-white border shadow-sm text-primary" @click="addOption" :disabled="optionsReadOnly">
                        <i class="bi bi-plus-lg me-1"></i> Add Option
                    </button>
                </h6>

                <div v-if="hasGeneratedOptionSource" class="alert alert-light border small text-secondary mb-3">
                    This item uses generated options from <span class="font-monospace">{{ optionSourceKind }}</span>.
                    Runtime-generated choices are preserved automatically and are not edited here.
                </div>

                <div v-for="(opt, idx) in localOptions" :key="idx" class="border rounded bg-white p-3 mb-3 shadow-sm">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small text-muted fw-semibold">Value</label>
                            <input type="text" class="form-control form-control-sm" v-model="opt.value" :disabled="optionsReadOnly" placeholder="Stored value">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-muted fw-semibold">Label</label>
                            <input type="text" class="form-control form-control-sm" v-model="opt.label" :disabled="optionsReadOnly" placeholder="Visible label">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button class="btn btn-white border text-danger w-100" @click="removeOption(idx)" :disabled="optionsReadOnly">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-md-4">
                            <div class="form-check mt-2">
                                <input
                                    :id="`option-exclusive-${idx}`"
                                    class="form-check-input"
                                    type="checkbox"
                                    v-model="opt.exclusive"
                                    :disabled="optionsReadOnly"
                                >
                                <label class="form-check-label small text-muted" :for="`option-exclusive-${idx}`">
                                    Exclusive option
                                </label>
                            </div>
                        </div>

                        <div v-if="allowsFreeTextMetadata" class="col-md-8">
                            <div class="form-check mt-2 mb-2">
                                <input
                                    :id="`option-freetext-${idx}`"
                                    class="form-check-input"
                                    type="checkbox"
                                    :checked="hasFreeTextOption(opt)"
                                    :disabled="optionsReadOnly"
                                    @change="toggleFreeText(opt, $event.target.checked)"
                                >
                                <label class="form-check-label small text-muted" :for="`option-freetext-${idx}`">
                                    Collect free-text details for this option
                                </label>
                            </div>

                            <input
                                v-if="hasFreeTextOption(opt)"
                                type="text"
                                class="form-control form-control-sm"
                                v-model="opt.meta.freetext_placeholder"
                                :disabled="optionsReadOnly"
                                placeholder="Placeholder shown in the follow-up field"
                            >
                        </div>
                    </div>
                </div>

                <div v-if="localOptions.length === 0" class="text-center text-muted small fst-italic py-3">
                    {{ hasGeneratedOptionSource
                        ? 'Generated choices are resolved from the option source at runtime.'
                        : 'No options defined. Click "Add Option" to start.' }}
                </div>
            </div>

            <div class="mb-4">
                <button
                    class="btn btn-light w-100 d-flex justify-content-between align-items-center p-3 border-0 bg-light text-secondary"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#logicEditor"
                >
                    <span><i class="bi bi-diagram-3 me-2"></i> Display Logic</span>
                    <i class="bi bi-chevron-down"></i>
                </button>
                <div class="collapse" id="logicEditor">
                    <div class="card card-body bg-light border-0 pt-0">
                        <hr class="mt-0 mb-3 opacity-25">
                        <logic-editor v-model="localItem.display_logic" :disabled="readOnly" />
                    </div>
                </div>
            </div>

            <div v-if="readOnly" class="alert alert-light border text-secondary">
                Live versions are read-only. Create a draft to change this question.
            </div>

            <div class="d-flex justify-content-end gap-2 pt-4 border-top">
                <button class="btn btn-light px-4" @click="$emit('cancel')">Cancel</button>
                <button class="btn btn-primary px-4 shadow-sm" @click="save" :disabled="saving || readOnly">
                    <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
                    {{ saving ? 'Saving...' : 'Save Changes' }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import LogicEditor from './LogicEditor.vue';

const props = defineProps({
    item: { type: Object, required: true },
    saving: { type: Boolean, default: false },
    readOnly: { type: Boolean, default: false },
});

const emit = defineEmits(['save', 'cancel']);

const clone = (value) => JSON.parse(JSON.stringify(value));

const normalizeOption = (option = {}, index = 0) => ({
    value: option.value ?? `option_${index + 1}`,
    label: option.label ?? '',
    exclusive: Boolean(option.exclusive),
    meta: { ...(option.meta ?? {}) },
});

const normalizeItem = (item) => {
    const normalized = clone(item);

    if (!normalized.scale_config) {
        normalized.scale_config = { min: 1, max: 5, step: 1 };
    }

    if (!normalized.display_logic) {
        normalized.display_logic = null;
    }

    return normalized;
};

const localItem = ref(normalizeItem(props.item));
const localOptions = ref((props.item.options ?? []).map(normalizeOption));

const supportsOptions = computed(() => ['dropdown', 'single_select', 'single_select_text', 'multi_select'].includes(localItem.value.type));
const allowsFreeTextMetadata = computed(() => localItem.value.type === 'single_select_text');
const hasGeneratedOptionSource = computed(() => Boolean(localItem.value.option_source?.kind));
const optionSourceKind = computed(() => localItem.value.option_source?.kind ?? null);
const optionsReadOnly = computed(() => props.readOnly || hasGeneratedOptionSource.value);

const hasFreeTextOption = (option) => Object.prototype.hasOwnProperty.call(option.meta ?? {}, 'freetext_placeholder');

const addOption = () => {
    if (optionsReadOnly.value) {
        return;
    }

    const nextIndex = localOptions.value.length + 1;
    localOptions.value.push(normalizeOption({
        value: `option_${nextIndex}`,
        label: `Option ${nextIndex}`,
        exclusive: false,
        meta: {},
    }, nextIndex - 1));
};

const removeOption = (idx) => {
    if (optionsReadOnly.value) {
        return;
    }

    localOptions.value.splice(idx, 1);
};

const toggleFreeText = (option, enabled) => {
    if (optionsReadOnly.value) {
        return;
    }

    option.meta = { ...(option.meta ?? {}) };

    if (enabled) {
        option.meta.freetext_placeholder = option.meta.freetext_placeholder ?? 'Please specify';
        return;
    }

    delete option.meta.freetext_placeholder;
};

const serializedOptions = () => {
    if (!supportsOptions.value) {
        return [];
    }

    return localOptions.value.map((option, index) => {
        const meta = { ...(option.meta ?? {}) };
        if (!allowsFreeTextMetadata.value) {
            delete meta.freetext_placeholder;
        }

        return {
            value: option.value,
            label: option.label,
            exclusive: Boolean(option.exclusive),
            meta,
            sort_order: index,
        };
    });
};

const save = () => {
    const payload = {
        ...clone(localItem.value),
        scale_config: localItem.value.type === 'slider' ? clone(localItem.value.scale_config ?? null) : null,
        options: serializedOptions(),
    };

    emit('save', payload);
};

watch(
    () => props.item,
    (newVal) => {
        localItem.value = normalizeItem(newVal);
        localOptions.value = (newVal.options ?? []).map(normalizeOption);
    },
    { deep: true }
);

watch(
    () => localItem.value.type,
    (type) => {
        if (type !== 'single_select_text') {
            localOptions.value = localOptions.value.map((option, index) => {
                const normalized = normalizeOption(option, index);
                delete normalized.meta.freetext_placeholder;
                return normalized;
            });
        }

        if (!supportsOptions.value) {
            localOptions.value = [];
        }
    }
);
</script>
