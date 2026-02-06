<template>
    <div class="survey-container mx-auto" style="max-width: 800px;">
        <Transition name="fade" mode="out-in">
            <div v-if="loading" class="text-center py-5" key="loading">
                <div class="card border-0 shadow-sm rounded-4 p-5">
                    <SkeletonLoader height="2rem" width="60%" class="mb-4 mx-auto" />
                    <SkeletonLoader height="1rem" width="80%" class="mb-2 mx-auto" />
                    <SkeletonLoader height="1rem" width="70%" class="mb-4 mx-auto" />
                    <div class="d-flex justify-content-center gap-3">
                        <SkeletonLoader height="3rem" width="120px" borderRadius="2rem" />
                        <SkeletonLoader height="3rem" width="120px" borderRadius="2rem" />
                    </div>
                </div>
            </div>
            
            <div v-else-if="error" class="alert alert-danger shadow-sm border-0 rounded-3" key="error">
                <i class="bi bi-exclamation-circle-fill me-2"></i> {{ error }}
            </div>
            
            <div v-else-if="completed" key="completed">
                <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                    <div class="card-body text-center py-5 px-4">
                        <div class="mb-4">
                            <div class="bg-success bg-opacity-10 text-success rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                <i class="bi bi-check-lg display-4"></i>
                            </div>
                        </div>
                        <h1 class="h3 fw-bold mb-3">Thank you!</h1>
                        <p class="text-muted mb-4 lead" v-if="alreadyCompleted">
                            You have already submitted this survey. We appreciate your time.
                        </p>
                        <p class="text-muted mb-4 lead" v-else>
                            Your responses have been recorded successfully.
                        </p>
                        <a href="/" class="btn btn-primary rounded-pill px-4 py-2">Return to Home</a>
                    </div>
                </div>
            </div>
            
            <div v-else key="survey">
                <!-- Progress Bar -->
                <div class="mb-4 px-1">
                    <div class="d-flex justify-content-between text-muted small mb-2 fw-semibold">
                        <span>Page {{ currentPageIndex + 1 }} of {{ totalPages }}</span>
                        <span>{{ Math.round(((currentPageIndex + 1) / totalPages) * 100) }}% Completed</span>
                    </div>
                    <div class="progress" style="height: 6px; background-color: #e9ecef;">
                        <div class="progress-bar bg-primary rounded-pill" role="progressbar" 
                             :style="{ width: `${((currentPageIndex + 1) / totalPages) * 100}%` }"></div>
                    </div>
                </div>

                <div class="card border-0 shadow-lg rounded-4">
                    <div class="card-body p-4 p-md-5">
                        <Transition name="slide-fade" mode="out-in">
                            <div :key="currentPageIndex">
                                <div class="mb-4 border-bottom pb-3">
                                    <h1 class="h4 fw-bold mb-2 text-primary">{{ currentPage?.title }}</h1>
                                    <p class="text-muted mb-0" v-if="currentPage?.attribute_label">{{ currentPage.attribute_label }}</p>
                                </div>

                                <div v-if="currentPage" class="survey-content">
                                    <div class="mb-4">
                                        <SurveyItem
                                            v-for="item in visibleItems(currentPage.items || [])"
                                            :key="item.qid"
                                            :item="item"
                                            :model-value="responses[item.qid]"
                                            :error="errors[item.qid]"
                                            :disabled="submitting"
                                            @update:modelValue="value => updateResponse(item.qid, value)"
                                            class="mb-4"
                                        />
                                    </div>

                                    <div v-for="section in currentPage.sections || []" :key="section.section_id" class="mb-5 p-4 bg-light rounded-3 border-start border-4 border-primary">
                                        <h2 class="h5 fw-bold mb-3 text-dark" v-if="section.title">{{ section.title }}</h2>
                                        <SurveyItem
                                            v-for="item in visibleItems(section.items || [])"
                                            :key="item.qid"
                                            :item="item"
                                            :model-value="responses[item.qid]"
                                            :error="errors[item.qid]"
                                            :disabled="submitting"
                                            @update:modelValue="value => updateResponse(item.qid, value)"
                                            class="mb-4"
                                        />
                                    </div>
                                </div>
                            </div>
                        </Transition>

                        <div class="d-flex justify-content-between align-items-center mt-5 pt-3 border-top">
                            <button class="btn btn-outline-secondary rounded-pill px-4" 
                                    :disabled="currentPageIndex === 0 || submitting" 
                                    @click="previousPage">
                                <i class="bi bi-arrow-left me-1"></i> Previous
                            </button>
                            
                            <div class="d-flex align-items-center gap-3">
                                <div class="d-none d-md-block text-end me-2">
                                    <small class="text-muted d-block lh-1" v-if="autosaveState.status === 'saving'">Saving...</small>
                                    <small class="text-muted d-block lh-1" v-else-if="autosaveState.status === 'saved'">Saved {{ autosaveState.timestamp }}</small>
                                    <small class="text-danger d-block lh-1" v-else-if="autosaveState.status === 'error'">Autosave failed</small>
                                </div>
                                
                                <button v-if="!isLastPage" class="btn btn-primary rounded-pill px-4 shadow-sm" 
                                        :disabled="submitting" 
                                        @click="nextPage">
                                    Next <i class="bi bi-arrow-right ms-1"></i>
                                </button>
                                <button v-else class="btn btn-success rounded-pill px-4 shadow-sm text-white" 
                                        :disabled="submitting" 
                                        @click="submitSurvey">
                                    <span v-if="submitting" class="spinner-border spinner-border-sm me-2" role="status"></span>
                                    Submit Survey
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.slide-fade-enter-active {
  transition: all 0.3s ease-out;
}

.slide-fade-leave-active {
  transition: all 0.2s cubic-bezier(1, 0.5, 0.8, 1);
}

.slide-fade-enter-from,
.slide-fade-leave-to {
  transform: translateX(20px);
  opacity: 0;
}
</style>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import axios from 'axios';
import SurveyItem from './SurveyItem.vue';
import SkeletonLoader from '../common/SkeletonLoader.vue';
import { useToast } from '../../composables/useToast';

const props = defineProps({
    definitionUrl: { type: String, required: true },
    submitUrl: { type: String, required: true },
    autosaveUrl: { type: String, required: true },
});

const toast = useToast();
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
        toast.error('Failed to load survey data.');
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
            toast.error('Autosave failed. Please check your connection.');
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

    const evaluator = (condition) => {
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
    };

    const operator = String(logic?.operator ?? logic?.combinator ?? 'and').toLowerCase();
    if (operator === 'or' || operator === 'any') {
        return conditions.some(evaluator);
    }

    return conditions.every(evaluator);
};

const isRequired = (item) =>
    item?.response?.required !== false &&
    ['slider', 'single_select', 'single_select_text', 'dropdown', 'multi_select', 'number_integer', 'text_short', 'text', 'text_long'].includes(item.type);

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

const isValidEmail = (value) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(value).trim());

const numberMin = (item) => {
    const min = item?.response?.min;
    return Number.isFinite(Number(min)) ? Number(min) : null;
};

const collectVisibleQids = () => {
    const qids = [];

    (pages.value || []).forEach((page) => {
        visibleItems(page.items || []).forEach((item) => qids.push(item.qid));
        (page.sections || []).forEach((section) => {
            visibleItems(section.items || []).forEach((item) => qids.push(item.qid));
        });
    });

    return new Set(qids);
};

const submissionResponses = () => {
    const payload = cloneResponses();
    const visibleQids = collectVisibleQids();

    return Object.fromEntries(
        Object.entries(payload).filter(([qid]) => visibleQids.has(qid))
    );
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
        const value = responses[item.qid];

        if (isRequired(item) && isEmpty(value)) {
            errors[item.qid] = 'Please provide an answer.';
            valid = false;
            return;
        }

        if (item.type === 'single_select_text' && value && typeof value === 'object' && value.selected && (!value.text || String(value.text).trim() === '')) {
            errors[item.qid] = 'Please provide the additional text.';
            valid = false;
            return;
        }

        if ((item.type === 'text_short' || item.type === 'text' || item.type === 'text_long') && item?.response?.format_hint === 'email' && !isEmpty(value) && !isValidEmail(value)) {
            errors[item.qid] = 'Please enter a valid email address.';
            valid = false;
            return;
        }

        if (item.type === 'number_integer' && !isEmpty(value)) {
            const numeric = Number(value);
            if (!Number.isInteger(numeric)) {
                errors[item.qid] = 'Please enter a whole number.';
                valid = false;
                return;
            }

            const min = numberMin(item);
            if (min !== null && numeric < min) {
                errors[item.qid] = `Value must be at least ${min}.`;
                valid = false;
                return;
            }
        }

        errors[item.qid] = null;
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
            responses: submissionResponses(),
            duration_ms: Date.now() - startTime,
        });
        completed.value = true;
    } catch (err) {
        if (err.response && err.response.status === 409) {
            completed.value = true;
            alreadyCompleted.value = true;
        } else if (err.response && err.response.status === 422) {
            const fieldErrors = err.response?.data?.errors || {};
            Object.keys(errors).forEach((qid) => {
                errors[qid] = null;
            });
            Object.entries(fieldErrors).forEach(([qid, messages]) => {
                const firstMessage = Array.isArray(messages) ? messages[0] : messages;
                errors[qid] = firstMessage || 'Invalid answer.';
            });

            const firstInvalidQid = Object.keys(fieldErrors)[0];
            if (firstInvalidQid) {
                const pageIndex = pages.value.findIndex((page) => {
                    const pageItemQids = visibleItems(page.items || []).map((item) => item.qid);
                    const sectionItemQids = (page.sections || [])
                        .flatMap((section) => visibleItems(section.items || []))
                        .map((item) => item.qid);

                    return [...pageItemQids, ...sectionItemQids].includes(firstInvalidQid);
                });

                if (pageIndex >= 0) {
                    currentPageIndex.value = pageIndex;
                }
            }

            error.value = 'Please correct the highlighted responses and submit again.';
        } else {
            console.error(err);
            error.value = 'Something went wrong while submitting your responses. Please try again.';
        }
    } finally {
        submitting.value = false;
    }
};
</script>
