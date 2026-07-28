<template>
    <div class="survey-container mx-auto" style="max-width: 800px;">
        <Transition name="fade" mode="out-in" @after-enter="focusActiveState">
            <div
                v-if="loading"
                class="text-center py-5"
                key="loading"
                role="status"
                aria-live="polite"
                aria-busy="true"
            >
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
            
            <div
                v-else-if="error"
                ref="loadError"
                class="alert alert-danger shadow-sm border-0 rounded-3"
                key="error"
                role="alert"
                tabindex="-1"
            >
                <i class="bi bi-exclamation-circle-fill me-2" aria-hidden="true"></i> {{ error }}
            </div>
            
            <div v-else-if="completed" key="completed">
                <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                    <div class="card-body text-center py-5 px-4">
                        <div class="mb-4">
                            <div class="bg-success bg-opacity-10 text-success rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                <i class="bi bi-check-lg display-4"></i>
                            </div>
                        </div>
                        <h1 ref="completedHeading" tabindex="-1" class="h3 fw-bold mb-3">Thank you!</h1>
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

            <div v-else-if="!privacyAccepted" key="privacy">
                <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                    <div class="card-body p-4 p-md-5">
                        <div class="small text-uppercase fw-semibold text-primary mb-2">Before you begin</div>
                        <h1 ref="privacyHeading" tabindex="-1" class="h3 fw-bold mb-3">{{ privacy.title }}</h1>
                        <p class="text-muted">{{ privacy.purpose }}</p>

                        <dl class="row small mb-4">
                            <dt class="col-sm-3">Identity</dt>
                            <dd class="col-sm-9">{{ privacy.identity }}</dd>
                            <dt class="col-sm-3">Who sees results</dt>
                            <dd class="col-sm-9">{{ privacy.visibility }}</dd>
                            <dt class="col-sm-3">WorkFit access</dt>
                            <dd class="col-sm-9">{{ privacy.workfit_access }}</dd>
                            <dt class="col-sm-3">Retention</dt>
                            <dd class="col-sm-9">{{ privacy.retention }}</dd>
                            <dt class="col-sm-3">Progress</dt>
                            <dd class="col-sm-9">{{ privacy.progress }}</dd>
                            <dt class="col-sm-3">Your rights</dt>
                            <dd class="col-sm-9">{{ privacy.rights }}</dd>
                        </dl>

                        <div class="alert alert-info border-0 small" role="note">
                            This survey is confidential at the reporting layer, but it is not anonymous. Empulse uses your identity for secure collection and cohort integrity.
                        </div>

                        <div class="form-check mb-4">
                            <input
                                id="privacy-acceptance"
                                v-model="privacyChecked"
                                class="form-check-input"
                                type="checkbox"
                                :disabled="acceptingPrivacy"
                                :aria-invalid="Boolean(privacyError)"
                                :aria-describedby="privacyError ? 'privacy-acceptance-error' : undefined"
                            >
                            <label class="form-check-label" for="privacy-acceptance">
                                I have read this respondent data promise (version {{ privacy.version }}).
                            </label>
                        </div>

                        <div
                            v-if="privacyError"
                            id="privacy-acceptance-error"
                            class="alert alert-danger"
                            role="alert"
                        >{{ privacyError }}</div>
                        <button
                            class="btn btn-primary rounded-pill px-4"
                            :disabled="!privacyChecked || acceptingPrivacy"
                            @click="acceptPrivacy"
                        >
                            <span v-if="acceptingPrivacy" class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>
                            Continue to survey
                        </button>
                        <div class="small text-muted mt-3">
                            Questions? Contact {{ privacy.contact }}.
                        </div>
                    </div>
                </div>
            </div>

            <div v-else key="survey">
                <div class="alert alert-light border-0 shadow-sm rounded-4 mb-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                        <div>
                            <div class="small text-uppercase fw-semibold text-secondary mb-2">Before you start</div>
                            <div class="small text-muted mb-2">
                                Your responses stay inside Empulse and are attached to this secure survey assignment.
                            </div>
                            <div class="small text-muted mb-0">
                                Progress autosaves while you move through the survey, so you can pause and return without losing your place.
                            </div>
                        </div>
                        <div class="text-md-end">
                            <div class="fw-semibold text-dark">{{ surveyMeta.estimated_minutes }} min</div>
                            <div class="small text-muted">{{ surveyMeta.question_count }} question{{ surveyMeta.question_count === 1 ? '' : 's' }}</div>
                        </div>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="mb-4 px-1">
                    <div
                        class="d-flex flex-wrap justify-content-between gap-2 text-muted small mb-2 fw-semibold"
                        aria-live="polite"
                        aria-atomic="true"
                    >
                        <span>Page {{ currentPageIndex + 1 }} of {{ totalPages }}</span>
                        <span>{{ Math.round(((currentPageIndex + 1) / totalPages) * 100) }}% complete</span>
                    </div>
                    <div class="progress" style="height: 6px; background-color: #e9ecef;">
                        <div class="progress-bar bg-primary rounded-pill" role="progressbar" 
                             aria-label="Survey progress"
                             :aria-valuemin="0"
                             :aria-valuemax="100"
                             :aria-valuenow="Math.round(((currentPageIndex + 1) / totalPages) * 100)"
                             :style="{ width: `${((currentPageIndex + 1) / totalPages) * 100}%` }"></div>
                    </div>
                </div>

                <div class="card border-0 shadow-lg rounded-4">
                    <div class="card-body p-4 p-md-5">
                        <Transition name="slide-fade" mode="out-in" @after-enter="focusPageHeading">
                            <div :key="currentPageIndex">
                                <div class="mb-4 border-bottom pb-3">
                                    <h1 ref="pageHeading" tabindex="-1" class="h4 fw-bold mb-2 text-primary">{{ currentPage?.title }}</h1>
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

                        <div class="survey-navigation d-flex flex-wrap justify-content-between align-items-center gap-3 mt-5 pt-3 border-top">
                            <button class="btn btn-outline-secondary rounded-pill px-4" 
                                    :disabled="currentPageIndex === 0 || submitting" 
                                    @click="previousPage">
                                <i class="bi bi-arrow-left me-1"></i> Previous
                            </button>
                            
                            <div class="survey-navigation-actions d-flex flex-wrap align-items-center justify-content-end gap-3">
                                <div class="text-end me-2" aria-live="polite" aria-atomic="true">
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
.survey-container {
  --survey-fade-duration: 0.3s;
  --survey-slide-enter-duration: 0.3s;
  --survey-slide-leave-duration: 0.2s;
}

.survey-container .btn {
  min-height: 44px;
}

.survey-container h1:focus-visible,
.survey-container [role="alert"]:focus-visible {
  outline: 3px solid #0b5ed7;
  outline-offset: 4px;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity var(--survey-fade-duration) ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.slide-fade-enter-active {
  transition: opacity var(--survey-slide-enter-duration) ease-out, transform var(--survey-slide-enter-duration) ease-out;
}

.slide-fade-leave-active {
  transition: opacity var(--survey-slide-leave-duration) cubic-bezier(1, 0.5, 0.8, 1), transform var(--survey-slide-leave-duration) cubic-bezier(1, 0.5, 0.8, 1);
}

.slide-fade-enter-from,
.slide-fade-leave-to {
  transform: translateX(20px);
  opacity: 0;
}

@media (prefers-reduced-motion: reduce) {
  .survey-container {
    --survey-fade-duration: 0s;
    --survey-slide-enter-duration: 0s;
    --survey-slide-leave-duration: 0s;
  }

  .fade-enter-active,
  .fade-leave-active,
  .slide-fade-enter-active,
  .slide-fade-leave-active {
    transition: none;
  }

  .fade-enter-from,
  .fade-leave-to,
  .slide-fade-enter-from,
  .slide-fade-leave-to {
    opacity: 1;
    transform: none;
  }
}

@media (max-width: 575.98px) {
  .survey-navigation > button,
  .survey-navigation-actions,
  .survey-navigation-actions > button {
    width: 100%;
  }

  .survey-navigation-actions {
    justify-content: stretch !important;
  }

  .survey-navigation-actions .text-end {
    width: 100%;
    min-height: 1rem;
    margin-right: 0 !important;
    text-align: left !important;
  }
}
</style>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import axios from 'axios';
import SurveyItem from './SurveyItem.vue';
import SkeletonLoader from '../common/SkeletonLoader.vue';
import { useToast } from '../../composables/useToast';

const props = defineProps({
    definitionUrl: { type: String, required: true },
    submitUrl: { type: String, required: true },
    autosaveUrl: { type: String, required: true },
    privacyAcknowledgmentUrl: { type: String, required: true },
});

const toast = useToast();
const loading = ref(true);
const error = ref(null);
const definition = ref(null);
const surveyMeta = ref({ question_count: 0, estimated_minutes: 4 });
const assignment = ref(null);
const privacy = ref({});
const privacyAccepted = ref(false);
const privacyChecked = ref(false);
const acceptingPrivacy = ref(false);
const privacyError = ref(null);
const pages = ref([]);
const currentPageIndex = ref(0);
const responses = reactive({});
const errors = reactive({});
const autosaveState = ref({ status: 'idle', timestamp: null });
const autosaveTimer = ref(null);
const draftRevision = ref(0);
const submitting = ref(false);
const completed = ref(false);
const alreadyCompleted = ref(false);
const hasLoaded = ref(false);
const startTime = Date.now();
const pageHeading = ref(null);
const privacyHeading = ref(null);
const completedHeading = ref(null);
const loadError = ref(null);

const currentPage = computed(() => pages.value[currentPageIndex.value] ?? null);
const totalPages = computed(() => pages.value.length);
const isLastPage = computed(() => currentPageIndex.value === totalPages.value - 1);

const fetchDefinition = async () => {
    loading.value = true;
    error.value = null;
    try {
        const { data } = await axios.get(props.definitionUrl);
        definition.value = data.version;
        surveyMeta.value = data.survey_meta || { question_count: 0, estimated_minutes: 4 };
        assignment.value = data.assignment;
        privacy.value = data.privacy || {};
        privacyAccepted.value =
            assignment.value.privacy_policy_version === privacy.value.version &&
            Boolean(assignment.value.privacy_acknowledged_at);
        draftRevision.value = Number(assignment.value.draft_revision || 0);
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

const acceptPrivacy = async () => {
    if (!privacyChecked.value || acceptingPrivacy.value) {
        return;
    }

    acceptingPrivacy.value = true;
    privacyError.value = null;
    try {
        const { data } = await axios.post(props.privacyAcknowledgmentUrl, { accepted: true });
        assignment.value.privacy_policy_version = data.policy_version;
        assignment.value.privacy_acknowledged_at = data.acknowledged_at;
        privacyAccepted.value = true;
    } catch (err) {
        console.error('Privacy acknowledgment failed', err);
        privacyError.value = 'We could not record your acknowledgment. Please try again before entering responses.';
    } finally {
        acceptingPrivacy.value = false;
    }
};

const handleVisibilityChange = () => {
    if (document.visibilityState === 'hidden' && hasLoaded.value && !completed.value) {
        clearTimeout(autosaveTimer.value);
        saveDraft();
    }
};

onMounted(() => {
    fetchDefinition();
    document.addEventListener('visibilitychange', handleVisibilityChange);
});

onBeforeUnmount(() => {
    clearTimeout(autosaveTimer.value);
    document.removeEventListener('visibilitychange', handleVisibilityChange);
});

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
    autosaveTimer.value = setTimeout(saveDraft, 1000);
};

const cloneResponses = () => JSON.parse(JSON.stringify(responses));

const saveDraft = async () => {
    try {
        autosaveState.value = { status: 'saving', timestamp: null };
        const { data } = await axios.post(props.autosaveUrl, {
            responses: cloneResponses(),
            revision: draftRevision.value,
        });
        draftRevision.value = Number(data.revision);
        autosaveState.value = { status: 'saved', timestamp: new Date().toLocaleTimeString() };
    } catch (err) {
        console.error('Autosave failed', err);
        autosaveState.value = { status: 'error', timestamp: null };
        if (err.response?.status === 409) {
            toast.error('A newer draft exists in another tab. Reload before continuing.');
            return;
        }
        toast.error('Autosave failed. Please check your connection.');
    }
};

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
    if (!validatePage()) {
        focusFirstError();
        return;
    }
    if (submitting.value) {
        return;
    }
    if (currentPageIndex.value < totalPages.value - 1) {
        currentPageIndex.value += 1;
    }
};

const submitSurvey = async () => {
    if (!validatePage()) {
        focusFirstError();
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
            Object.entries(fieldErrors).forEach(([field, messages]) => {
                const qid = field.startsWith('responses.')
                    ? field.slice('responses.'.length)
                    : field;
                const firstMessage = Array.isArray(messages) ? messages[0] : messages;
                errors[qid] = firstMessage || 'Invalid answer.';
            });

            const firstInvalidField = Object.keys(fieldErrors)[0];
            const firstInvalidQid = firstInvalidField
                ? (firstInvalidField.startsWith('responses.')
                    ? firstInvalidField.slice('responses.'.length)
                    : firstInvalidField)
                : null;
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
                    nextTick(focusFirstError);
                }
            }

            error.value = 'Please correct the highlighted responses and submit again.';
        } else if (err.response && err.response.status === 428) {
            privacyAccepted.value = false;
            privacyChecked.value = false;
            privacyError.value = 'The respondent data promise changed. Please review the current version.';
        } else {
            console.error(err);
            error.value = 'Something went wrong while submitting your responses. Please try again.';
        }
    } finally {
        submitting.value = false;
    }
};

const focusFirstError = () => {
    nextTick(() => {
        const firstQid = Object.keys(errors).find((qid) => Boolean(errors[qid]));
        if (!firstQid) return;
        const escaped = window.CSS?.escape ? window.CSS.escape(firstQid) : firstQid.replace(/"/g, '\\"');
        const item = document.querySelector(`[data-qid="${escaped}"]`);
        item?.querySelector('[data-error-focus="true"]')?.focus();
        if (item?.contains(document.activeElement)) {
            return;
        }
        item?.querySelector('input, select, textarea')?.focus();
    });
};

const focusPageHeading = () => {
    nextTick(() => pageHeading.value?.focus());
};

const focusActiveState = () => {
    nextTick(() => {
        if (completed.value) {
            completedHeading.value?.focus();
            return;
        }
        if (error.value) {
            loadError.value?.focus();
            return;
        }
        if (!privacyAccepted.value) {
            privacyHeading.value?.focus();
            return;
        }
        pageHeading.value?.focus();
    });
};

watch(currentPageIndex, focusPageHeading);
</script>
