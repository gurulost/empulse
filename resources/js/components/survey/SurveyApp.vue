<template>
    <div>
        <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Loading...</span>
            </div>
        </div>
        <div v-else-if="error" class="alert alert-danger">{{ error }}</div>
        <div v-else-if="completed">
            <div class="card shadow-sm">
                <div class="card-body text-center py-5">
                    <h1 class="h4 mb-3">Thank you!</h1>
                    <p class="text-muted mb-0" v-if="alreadyCompleted">
                        You have already submitted this survey. We appreciate your time.
                    </p>
                    <p class="text-muted mb-0" v-else>
                        Your responses have been recorded.
                    </p>
                </div>
            </div>
        </div>
        <div v-else>
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h1 class="h5 mb-1">{{ currentPage?.title }}</h1>
                            <p class="text-muted mb-0" v-if="currentPage?.attribute_label">{{ currentPage.attribute_label }}</p>
                        </div>
                        <div class="text-muted small">Page {{ currentPageIndex + 1 }} of {{ totalPages }}</div>
                    </div>

                    <div v-if="currentPage">
                        <SurveyItem
                            v-for="item in visibleItems(currentPage.items || [])"
                            :key="item.qid"
                            :item="item"
                            :model-value="responses[item.qid]"
                            :error="errors[item.qid]"
                            :disabled="submitting"
                            @update:modelValue="value => updateResponse(item.qid, value)"
                        />

                        <div v-for="section in currentPage.sections || []" :key="section.section_id" class="mb-4">
                            <h2 class="h6 mb-3" v-if="section.title">{{ section.title }}</h2>
                            <SurveyItem
                                v-for="item in visibleItems(section.items || [])"
                                :key="item.qid"
                                :item="item"
                                :model-value="responses[item.qid]"
                                :error="errors[item.qid]"
                                :disabled="submitting"
                                @update:modelValue="value => updateResponse(item.qid, value)"
                            />
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <button class="btn btn-outline-secondary" :disabled="currentPageIndex === 0 || submitting" @click="previousPage">
                                Previous
                            </button>
                            <div class="d-flex align-items-center gap-3">
                                <small class="text-muted" v-if="autosaveState.status === 'saving'">Saving…</small>
                                <small class="text-muted" v-else-if="autosaveState.status === 'saved'">Saved {{ autosaveState.timestamp }}</small>
                                <small class="text-danger" v-else-if="autosaveState.status === 'error'">Autosave failed</small>
                                <button v-if="!isLastPage" class="btn btn-primary" :disabled="submitting" @click="nextPage">
                                    Next
                                </button>
                                <button v-else class="btn btn-success" :disabled="submitting" @click="submitSurvey">
                                    <span v-if="submitting" class="spinner-border spinner-border-sm me-2" role="status"></span>
                                    Submit
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import axios from 'axios';
import SurveyItem from './SurveyItem.vue';

const props = defineProps({
    definitionUrl: { type: String, required: true },
    submitUrl: { type: String, required: true },
    autosaveUrl: { type: String, required: true },
});

const loading = ref(true);
const error = ref(null);
const definition = ref(null);
const assignment = ref(null);
const pages = ref([]);
const currentPageIndex = ref(0);
const responses = reactive({});
const errors = reactive({});
const autosaveState = ref({ status: 'idle', timestamp: null });
const autosaveTimer = ref(null);
const submitting = ref(false);
const completed = ref(false);
const alreadyCompleted = ref(false);
const hasLoaded = ref(false);
const startTime = Date.now();

const currentPage = computed(() => pages.value[currentPageIndex.value] ?? null);
const totalPages = computed(() => pages.value.length);
const isLastPage = computed(() => currentPageIndex.value === totalPages.value - 1);

const fetchDefinition = async () => {
    loading.value = true;
    error.value = null;
    try {
        const { data } = await axios.get(props.definitionUrl);
        definition.value = data.version;
        assignment.value = data.assignment;
        pages.value = data.pages || [];
        currentPageIndex.value = 0;
        alreadyCompleted.value = assignment.value.status === 'completed';
        completed.value = alreadyCompleted.value;

        Object.keys(responses).forEach((key) => delete responses[key]);
        const draft = assignment.value.draft_answers || {};
        Object.entries(draft).forEach(([key, value]) => {
            responses[key] = value;
        });
        hasLoaded.value = true;
    } catch (err) {
        console.error(err);
        error.value = 'Unable to load the survey right now. Please try again later.';
    } finally {
        loading.value = false;
    }
};

onMounted(fetchDefinition);

watch(
    responses,
    () => {
        if (!hasLoaded.value || completed.value) {
            return;
        }
        scheduleAutosave();
    },
    { deep: true }
);

const scheduleAutosave = () => {
    clearTimeout(autosaveTimer.value);
    autosaveTimer.value = setTimeout(async () => {
        try {
            autosaveState.value = { status: 'saving', timestamp: null };
            await axios.post(props.autosaveUrl, { responses: cloneResponses() });
            autosaveState.value = { status: 'saved', timestamp: new Date().toLocaleTimeString() };
        } catch (err) {
            console.error('Autosave failed', err);
            autosaveState.value = { status: 'error', timestamp: null };
        }
    }, 1000);
};

const cloneResponses = () => JSON.parse(JSON.stringify(responses));

const updateResponse = (qid, value) => {
    responses[qid] = value;
    errors[qid] = null;
};

const visibleItems = (items = []) => items.filter((item) => shouldDisplay(item));

const shouldDisplay = (item) => {
    const logic = item.display_logic;
    if (!logic || (Array.isArray(logic) && logic.length === 0)) {
        return true;
    }

    const conditions = Array.isArray(logic) ? logic : logic.when ?? [];
    if (!conditions.length) {
        return true;
    }

    return conditions.every((condition) => {
        const currentValue = responses[condition.qid];
        if (currentValue === undefined || currentValue === null || currentValue === '') {
            return false;
        }

        const equalsAny = condition.equals_any || [];
        if (!equalsAny.length) {
            return true;
        }
        if (Array.isArray(currentValue)) {
            return currentValue.some((v) => equalsAny.includes(v));
        }

        if (typeof currentValue === 'object') {
            const candidate = currentValue.selected ?? currentValue.value ?? currentValue.text ?? '';
            return equalsAny.includes(candidate);
        }

        return equalsAny.includes(currentValue);
    });
};

const isRequired = (item) => ['slider', 'single_select', 'single_select_text', 'dropdown', 'multi_select', 'number_integer'].includes(item.type);

const isEmpty = (value) => {
    if (value === null || value === undefined || value === '') {
        return true;
    }

    if (Array.isArray(value)) {
        return value.length === 0;
    }

    if (typeof value === 'object') {
        const keys = Object.keys(value).filter((key) => value[key] !== undefined && value[key] !== '');
        return keys.length === 0;
    }

    return false;
};

const validatePage = () => {
    const page = currentPage.value;
    if (!page) {
        return true;
    }

    let valid = true;
    const sectionItems = (page.sections || []).flatMap((section) => visibleItems(section.items || []));
    const toCheck = [...visibleItems(page.items || []), ...sectionItems];

    toCheck.forEach((item) => {
        if (isRequired(item) && isEmpty(responses[item.qid])) {
            errors[item.qid] = 'Please provide an answer.';
            valid = false;
        } else {
            errors[item.qid] = null;
        }
    });

    return valid;
};

const previousPage = () => {
    if (currentPageIndex.value === 0 || submitting.value) {
        return;
    }
    currentPageIndex.value -= 1;
};

const nextPage = () => {
    if (!validatePage() || submitting.value) {
        return;
    }
    if (currentPageIndex.value < totalPages.value - 1) {
        currentPageIndex.value += 1;
    }
};

const submitSurvey = async () => {
    if (!validatePage()) {
        return;
    }

    submitting.value = true;
    error.value = null;
    clearTimeout(autosaveTimer.value);
    try {
        await axios.post(props.submitUrl, {
            responses: cloneResponses(),
            duration_ms: Date.now() - startTime,
        });
        completed.value = true;
    } catch (err) {
        if (err.response && err.response.status === 409) {
            completed.value = true;
            alreadyCompleted.value = true;
        } else {
            console.error(err);
            error.value = 'Something went wrong while submitting your responses. Please try again.';
        }
    } finally {
        submitting.value = false;
    }
};
</script>
