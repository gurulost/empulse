<template>
    <div class="mb-4">
        <label class="form-label fw-semibold">{{ item.question }}</label>
        <p class="text-muted small" v-if="item.metadata?.note">{{ item.metadata.note }}</p>

        <template v-if="item.type === 'slider'">
            <div class="d-flex justify-content-between text-muted small mb-1">
                <span>{{ scaleLabels.left }}</span>
                <span v-if="scaleLabels.mid">{{ scaleLabels.mid }}</span>
                <span>{{ scaleLabels.right }}</span>
            </div>
            <input type="range"
                   class="form-range"
                   :min="scale.min"
                   :max="scale.max"
                   :step="scale.step || 1"
                   v-model.number="sliderValue"
                   :disabled="disabled"
            />
            <div class="text-end text-muted small">Value: {{ sliderValue }}</div>
        </template>

        <template v-else-if="item.type === 'text_short' || item.type === 'text'">
            <input type="text" class="form-control" :value="textValue" :disabled="disabled" @input="updateText($event.target.value)" />
        </template>

        <template v-else-if="item.type === 'text_long'">
            <textarea class="form-control" rows="4" :value="textValue" :disabled="disabled" @input="updateText($event.target.value)"></textarea>
        </template>

        <template v-else-if="item.type === 'number_integer'">
            <input type="number" class="form-control" :value="numberValue" :disabled="disabled" @input="updateNumber($event.target.value)" />
        </template>

        <template v-else-if="item.type === 'dropdown' || item.type === 'single_select' || item.type === 'single_select_text'">
            <select class="form-select" :value="selectValue" :disabled="disabled" @change="onSelectChange($event.target.value)">
                <option value="" disabled>Select an option</option>
                <option v-for="option in options" :key="option.value" :value="option.value">
                    {{ option.label }}
                </option>
            </select>
            <div v-if="showFreeText" class="mt-2">
                <input type="text" class="form-control" :placeholder="freeTextPlaceholder" :value="freeText" :disabled="disabled" @input="onFreeTextChange($event.target.value)" />
            </div>
        </template>

        <template v-else-if="item.type === 'multi_select'">
            <div class="d-flex flex-column gap-1">
                <div v-for="option in options" :key="option.value" class="form-check">
                    <input class="form-check-input"
                           type="checkbox"
                           :id="item.qid + '-' + option.value"
                           :value="option.value"
                           :checked="multiSelectValues.includes(option.value)"
                           :disabled="disabled"
                           @change="onMultiSelectToggle(option)"
                    >
                    <label class="form-check-label" :for="item.qid + '-' + option.value">{{ option.label }}</label>
                </div>
            </div>
        </template>

        <template v-else>
            <input type="text" class="form-control" :value="textValue" :disabled="disabled" @input="updateText($event.target.value)" />
        </template>

        <div v-if="error" class="text-danger small mt-1">{{ error }}</div>
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
    item: { type: Object, required: true },
    modelValue: { default: null },
    error: { type: String, default: '' },
    disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const options = computed(() => props.item.options || []);

const scale = computed(() => {
    const defaults = { min: 1, max: 5, step: 1 };
    if (!props.item.scale) {
        return defaults;
    }
    return { ...defaults, ...props.item.scale };
});

const scaleLabels = computed(() => ({
    left: props.item.scale?.left_label ?? 'Low',
    right: props.item.scale?.right_label ?? 'High',
    mid: props.item.scale?.mid_label ?? null,
}));

const sliderValue = ref(getInitialSliderValue());
watch(
    () => props.modelValue,
    (val) => {
        if (props.item.type === 'slider' && val !== undefined && val !== null && val !== '') {
            sliderValue.value = Number(val);
        }
    }
);
watch(
    () => sliderValue.value,
    (val) => {
        if (props.item.type === 'slider') {
            emit('update:modelValue', val);
        }
    }
);

function getInitialSliderValue() {
    if (props.modelValue !== null && props.modelValue !== undefined) {
        return Number(props.modelValue);
    }
    const min = scale.value.min ?? 1;
    const max = scale.value.max ?? 5;
    return Math.round((min + max) / 2);
}

const textValue = computed(() => props.modelValue ?? '');
const numberValue = computed(() => (props.modelValue ?? ''));
const selectValue = computed(() => {
    if (props.item.type === 'single_select_text') {
        if (props.modelValue && typeof props.modelValue === 'object') {
            return props.modelValue.selected ?? '';
        }
    }
    return props.modelValue ?? '';
});
const freeText = computed(() => {
    if (props.item.type === 'single_select_text' && props.modelValue && typeof props.modelValue === 'object') {
        return props.modelValue.text ?? '';
    }
    return '';
});
const freeTextPlaceholder = computed(() => {
    const option = options.value.find((opt) => opt.value === selectValue.value);
    return option?.meta?.freetext_placeholder ?? 'Please specify';
});
const showFreeText = computed(() => {
    const option = options.value.find((opt) => opt.value === selectValue.value);
    return props.item.type === 'single_select_text' && option?.meta?.freetext_placeholder;
});

const multiSelectValues = computed(() => {
    if (Array.isArray(props.modelValue)) {
        return props.modelValue;
    }
    return [];
});

const exclusiveValues = computed(() => options.value.filter((opt) => opt.exclusive).map((opt) => opt.value));

const updateText = (value) => emit('update:modelValue', value);
const updateNumber = (value) => {
    emit('update:modelValue', value === '' ? null : Number(value));
};

const onSelectChange = (value) => {
    if (props.item.type === 'single_select_text') {
        const option = options.value.find((opt) => opt.value === value);
        if (option?.meta?.freetext_placeholder) {
            emit('update:modelValue', { selected: value, text: props.modelValue?.text ?? '' });
        } else {
            emit('update:modelValue', value);
        }
    } else {
        emit('update:modelValue', value);
    }
};

const onFreeTextChange = (text) => {
    emit('update:modelValue', { selected: selectValue.value, text });
};

const onMultiSelectToggle = (option) => {
    const current = Array.isArray(props.modelValue) ? [...props.modelValue] : [];
    const exists = current.includes(option.value);

    let nextValues;
    if (option.exclusive) {
        nextValues = exists ? [] : [option.value];
    } else {
        nextValues = current.filter((val) => !exclusiveValues.value.includes(val));
        if (exists) {
            nextValues = nextValues.filter((val) => val !== option.value);
        } else {
            nextValues.push(option.value);
        }
    }

    emit('update:modelValue', nextValues);
};
</script>
