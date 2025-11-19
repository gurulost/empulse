<template>
    <div class="logic-editor">
        <div v-if="rules.length === 0" class="text-center p-3 bg-light rounded mb-3">
            <small class="text-muted">No display logic defined. This question will always be shown.</small>
        </div>

        <div v-else class="list-group mb-3">
            <div v-for="(rule, idx) in rules" :key="idx" class="list-group-item bg-light">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-secondary">IF</span>
                    <input type="text" class="form-control form-control-sm" 
                           v-model="rule.qid" 
                           placeholder="Question ID (e.g. q1)"
                           style="width: 120px;">
                    
                    <select class="form-select form-select-sm" style="width: 100px;" disabled>
                        <option>Equals</option>
                    </select>

                    <div class="flex-grow-1">
                        <input type="text" class="form-control form-control-sm" 
                               v-model="rule.value" 
                               placeholder="Value to match">
                    </div>

                    <button class="btn btn-outline-danger btn-sm" @click="removeRule(idx)">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        </div>

        <button class="btn btn-sm btn-outline-primary w-100" @click="addRule">
            <i class="bi bi-plus-circle me-1"></i> Add Logic Rule
        </button>
    </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';

const props = defineProps({
    modelValue: { type: Object, default: () => ({}) }
});

const emit = defineEmits(['update:modelValue']);

const rules = ref([]);

// Parse existing logic into UI rules
const parseLogic = (logic) => {
    if (!logic || !logic.when) return [];
    
    return logic.when.map(condition => ({
        qid: condition.qid,
        value: condition.equals_any ? condition.equals_any[0] : '' // Simplified for UI
    }));
};

// Initialize
onMounted(() => {
    rules.value = parseLogic(props.modelValue);
});

watch(() => props.modelValue, (newVal) => {
    // Only update if different to avoid loops, simple check
    const currentJson = JSON.stringify(formatLogic(rules.value));
    const newJson = JSON.stringify(newVal);
    if (currentJson !== newJson) {
        rules.value = parseLogic(newVal);
    }
});

const formatLogic = (uiRules) => {
    if (uiRules.length === 0) return null;

    return {
        when: uiRules.map(r => ({
            qid: r.qid,
            equals_any: [r.value]
        }))
    };
};

const addRule = () => {
    rules.value.push({ qid: '', value: '' });
    emitUpdate();
};

const removeRule = (idx) => {
    rules.value.splice(idx, 1);
    emitUpdate();
};

const emitUpdate = () => {
    emit('update:modelValue', formatLogic(rules.value));
};

watch(rules, () => {
    emitUpdate();
}, { deep: true });
</script>
