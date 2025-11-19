<template>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 pt-4 px-4 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-1 fw-bold">Edit Question</h5>
                <span class="badge bg-light text-secondary border rounded-pill fw-normal font-monospace">{{ item.qid }}</span>
            </div>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3">{{ item.type }}</span>
        </div>
        <div class="card-body p-4">
            <!-- Basic Info -->
            <div class="mb-4">
                <label class="form-label text-secondary small fw-bold text-uppercase">Question Text</label>
                <input type="text" class="form-control form-control-lg" v-model="localItem.question" placeholder="Enter your question here...">
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <label class="form-label text-secondary small fw-bold text-uppercase">Type</label>
                    <select class="form-select" v-model="localItem.type">
                        <option value="slider">Slider (Scale)</option>
                        <option value="text">Text Input</option>
                        <option value="text_long">Long Text</option>
                        <option value="number_integer">Number (Integer)</option>
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

            <!-- Type Specific Config -->
            <div v-if="localItem.type === 'slider'" class="mb-4 p-4 rounded bg-light border-0">
                <h6 class="mb-3 fw-bold text-secondary">Scale Configuration</h6>
                <div class="row g-3">
                    <div class="col-4">
                        <label class="small text-muted fw-semibold">Min</label>
                        <input type="number" class="form-control form-control-sm" v-model.number="localItem.scale_config.min">
                    </div>
                    <div class="col-4">
                        <label class="small text-muted fw-semibold">Max</label>
                        <input type="number" class="form-control form-control-sm" v-model.number="localItem.scale_config.max">
                    </div>
                    <div class="col-4">
                        <label class="small text-muted fw-semibold">Step</label>
                        <input type="number" class="form-control form-control-sm" v-model.number="localItem.scale_config.step">
                    </div>
                    <div class="col-6">
                        <label class="small text-muted fw-semibold">Left Label</label>
                        <input type="text" class="form-control form-control-sm" v-model="localItem.scale_config.left_label" placeholder="e.g. Disagree">
                    </div>
                    <div class="col-6">
                        <label class="small text-muted fw-semibold">Right Label</label>
                        <input type="text" class="form-control form-control-sm" v-model="localItem.scale_config.right_label" placeholder="e.g. Agree">
                    </div>
                </div>
            </div>

            <div v-if="['single_select', 'multi_select', 'single_select_text'].includes(localItem.type)" class="mb-4 p-4 rounded bg-light border-0">
                <h6 class="mb-3 d-flex justify-content-between align-items-center fw-bold text-secondary">
                    Options
                    <button class="btn btn-sm btn-white border shadow-sm text-primary" @click="addOption">
                        <i class="bi bi-plus-lg me-1"></i> Add Option
                    </button>
                </h6>
                <div v-for="(opt, idx) in localOptions" :key="idx" class="input-group mb-2 shadow-sm">
                    <span class="input-group-text bg-white border-end-0 text-muted small">{{ opt.value }}</span>
                    <input type="text" class="form-control border-start-0" v-model="opt.label" placeholder="Label">
                    <button class="btn btn-white border text-danger" @click="removeOption(idx)">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
                <div v-if="localOptions.length === 0" class="text-center text-muted small fst-italic py-3">
                    No options defined. Click "Add Option" to start.
                </div>
            </div>

            <!-- Logic Editor -->
            <div class="mb-4">
                <button class="btn btn-light w-100 d-flex justify-content-between align-items-center p-3 border-0 bg-light text-secondary" 
                        type="button" data-bs-toggle="collapse" data-bs-target="#logicEditor">
                    <span><i class="bi bi-diagram-3 me-2"></i> Display Logic</span>
                    <i class="bi bi-chevron-down"></i>
                </button>
                <div class="collapse" id="logicEditor">
                    <div class="card card-body bg-light border-0 pt-0">
                        <hr class="mt-0 mb-3 opacity-25">
                        <logic-editor v-model="localItem.display_logic" />
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="d-flex justify-content-end gap-2 pt-4 border-top">
                <button class="btn btn-light px-4" @click="$emit('cancel')">Cancel</button>
                <button class="btn btn-primary px-4 shadow-sm" @click="save" :disabled="saving">
                    <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
                    {{ saving ? 'Saving...' : 'Save Changes' }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import LogicEditor from './LogicEditor.vue';

const props = defineProps({
    item: { type: Object, required: true },
    saving: { type: Boolean, default: false }
});

const emit = defineEmits(['save', 'cancel']);

const localItem = ref(JSON.parse(JSON.stringify(props.item)));
const localOptions = ref(props.item.options ? JSON.parse(JSON.stringify(props.item.options)) : []);

// Ensure config objects exist
if (!localItem.value.scale_config) localItem.value.scale_config = { min: 1, max: 5, step: 1 };

const logicJson = computed({
    get: () => JSON.stringify(localItem.value.display_logic || {}, null, 2),
    set: (val) => {
        try {
            localItem.value.display_logic = JSON.parse(val);
        } catch (e) {
            // Invalid JSON, ignore or show error
        }
    }
});

const addOption = () => {
    const val = localOptions.value.length + 1;
    localOptions.value.push({ value: val, label: `Option ${val}` });
};

const removeOption = (idx) => {
    localOptions.value.splice(idx, 1);
};

const save = () => {
    // Merge options back into item for saving
    const payload = { ...localItem.value, options: localOptions.value };
    emit('save', payload);
};

watch(() => props.item, (newVal) => {
    localItem.value = JSON.parse(JSON.stringify(newVal));
    localOptions.value = newVal.options ? JSON.parse(JSON.stringify(newVal.options)) : [];
    if (!localItem.value.scale_config) localItem.value.scale_config = { min: 1, max: 5, step: 1 };
});
</script>
