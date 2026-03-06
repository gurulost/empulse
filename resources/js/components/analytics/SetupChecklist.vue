<template>
    <div class="card border-0 shadow-sm h-100 setup-card">
        <div class="card-body p-4 p-xl-5">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
                <div>
                    <div class="setup-kicker">First Survey Activation</div>
                    <h2 class="h4 fw-bold mb-2">Get to your first completed response</h2>
                    <p class="text-muted mb-0">
                        Empulse starts paying off when one real survey response lands and the dashboard comes alive.
                        These steps are generated from the current company setup.
                    </p>
                </div>
                <div class="setup-progress-chip">
                    <div class="setup-progress-value">{{ completedSteps }}/{{ steps.length }}</div>
                    <div class="small text-muted">steps complete</div>
                </div>
            </div>

            <div class="progress mb-4 setup-progress-bar">
                <div
                    class="progress-bar rounded-pill"
                    role="progressbar"
                    :style="{ width: `${progressPercent}%` }"
                    :aria-valuenow="progressPercent"
                    aria-valuemin="0"
                    aria-valuemax="100"
                ></div>
            </div>

            <div class="d-flex flex-column gap-3">
                <div
                    v-for="step in steps"
                    :key="step.id"
                    class="setup-step"
                    :class="{ 'setup-step-complete': step.complete, 'setup-step-current': step.isCurrent }"
                >
                    <div class="d-flex gap-3 align-items-start">
                        <div class="setup-step-icon" :class="step.complete ? 'setup-step-icon-complete' : 'setup-step-icon-pending'">
                            <i :class="step.icon"></i>
                        </div>

                        <div class="flex-grow-1">
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                                <div>
                                    <div class="fw-semibold text-dark">{{ step.title }}</div>
                                    <div class="small text-muted mt-1">{{ step.description }}</div>
                                </div>

                                <span
                                    class="badge rounded-pill px-3 py-2 setup-status-badge"
                                    :class="step.complete ? 'text-bg-success' : (step.isCurrent ? 'text-bg-primary' : 'text-bg-secondary')"
                                >
                                    {{ step.statusLabel }}
                                </span>
                            </div>

                            <div v-if="step.detail" class="small mt-2" :class="step.complete ? 'text-muted' : 'text-dark'">
                                {{ step.detail }}
                            </div>

                            <div v-if="step.ctaHref && step.ctaLabel" class="mt-3">
                                <a
                                    :href="step.ctaHref"
                                    class="btn btn-sm rounded-pill px-3"
                                    :class="step.isCurrent ? 'btn-primary' : 'btn-outline-secondary'"
                                    @click.prevent="handleCta(step)"
                                >
                                    {{ step.ctaLabel }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="alert alert-light border rounded-4 mt-4 mb-0">
                <div class="small text-uppercase fw-semibold text-secondary mb-1">First meaningful success</div>
                <div class="small text-muted">
                    Success is not just creating a wave. It is getting the first completed survey back so the dashboard,
                    indicators, and gap analysis populate with live company data.
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    setup: {
        type: Object,
        default: () => ({}),
    },
    user: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['cta-click']);

const role = computed(() => Number(props.user?.role ?? 0));
const canAccessBilling = computed(() => role.value === 1);
const canManageSurveyContent = computed(() => Boolean(props.setup?.can_manage_survey_content));

const formatCount = (count, singular, plural = `${singular}s`) => `${count} ${count === 1 ? singular : plural}`;

const firstIncompleteIndex = computed(() => {
    const index = rawSteps.value.findIndex((step) => !step.complete);
    return index === -1 ? null : index;
});

const rawSteps = computed(() => {
    const setup = props.setup ?? {};
    const recipientCount = Number(setup.recipient_count ?? 0);
    const departmentCount = Number(setup.department_count ?? 0);
    const billingAllowsScheduling = Boolean(setup.billing_allows_scheduling);
    const hasLiveSurvey = Boolean(setup.has_live_survey);
    const hasDispatchedWave = Boolean(setup.has_dispatched_wave);
    const responseCount = Number(setup.response_count ?? 0);
    const waveCount = Number(setup.wave_count ?? 0);
    const latestWave = setup.latest_wave ?? null;
    const liveSurvey = setup.live_survey ?? null;

    const surveyStep = hasLiveSurvey
        ? {
            description: `Live survey version ${liveSurvey?.version ?? ''} is ready to send.`,
            detail: liveSurvey?.title ? liveSurvey.title : 'The active survey definition is available for new waves.',
            ctaHref: '',
            ctaLabel: '',
        }
        : canManageSurveyContent.value
            ? {
                description: 'A live survey version must be published before any wave can be sent.',
                detail: 'Open the survey builder and publish one active version for managers to use.',
                ctaHref: '/admin/builder',
                ctaLabel: 'Open Survey Builder',
            }
            : {
                description: 'A Workfit admin must activate the shared live survey before your company can dispatch a wave.',
                detail: 'Survey content is admin-owned in Empulse. Contact Workfit admin if this step is still blocking your launch.',
                ctaHref: '/contact',
                ctaLabel: 'Contact Workfit Admin',
            };

    const waveDescription = hasDispatchedWave
        ? `A wave has already been dispatched${latestWave?.label ? `: ${latestWave.label}` : ''}.`
        : waveCount > 0
            ? `You already created ${formatCount(waveCount, 'wave')}, but none has been dispatched yet.`
            : 'Create and dispatch your first wave to send secure survey links.';

    const responseDescription = responseCount > 0
        ? `${formatCount(responseCount, 'response')} received so far.`
        : hasDispatchedWave
            ? 'Invites have gone out. The dashboard will unlock automatically after the first submission.'
            : 'The dashboard fills in after a dispatched wave receives its first completed survey.';

    return [
        {
            id: 'team',
            icon: 'bi bi-people',
            title: 'Add survey recipients',
            complete: recipientCount > 0,
            description: recipientCount > 0
                ? `${formatCount(recipientCount, 'recipient')} can receive the first wave.`
                : 'Add at least one chief, team lead, or employee before you try to send a wave.',
            detail: departmentCount > 0
                ? `${formatCount(departmentCount, 'department')} already set up for filtering and comparisons.`
                : 'Departments are optional for launch, but creating them now makes later reporting easier.',
            ctaHref: '/team/manage',
            ctaLabel: recipientCount > 0 ? 'Review Team' : 'Add Team Members',
        },
        {
            id: 'billing',
            icon: 'bi bi-credit-card',
            title: 'Confirm billing can dispatch waves',
            complete: billingAllowsScheduling,
            description: billingAllowsScheduling
                ? props.setup?.billing_label ?? 'Billing is active.'
                : 'Wave dispatch is blocked until billing is active for this company.',
            detail: props.setup?.plan_label
                ? `Current plan: ${props.setup.plan_label}.`
                : 'Update billing before launching your first survey.',
            ctaHref: canAccessBilling.value ? '/account/billing' : '',
            ctaLabel: canAccessBilling.value
                ? (billingAllowsScheduling ? 'View Billing' : 'Open Billing')
                : '',
        },
        {
            id: 'survey',
            icon: 'bi bi-ui-checks-grid',
            title: 'Verify the live survey',
            complete: hasLiveSurvey,
            ...surveyStep,
        },
        {
            id: 'wave',
            icon: 'bi bi-send-check',
            title: 'Dispatch the first wave',
            complete: hasDispatchedWave,
            description: waveDescription,
            detail: latestWave
                ? `Latest wave status: ${latestWave.status ?? 'unknown'}${latestWave.label ? ` for ${latestWave.label}` : ''}.`
                : 'Use a full wave for the fastest path to first data.',
            ctaHref: '/survey-waves',
            ctaLabel: hasDispatchedWave ? 'Review Waves' : 'Open Survey Waves',
        },
        {
            id: 'responses',
            icon: 'bi bi-graph-up-arrow',
            title: 'Capture the first completed response',
            complete: responseCount > 0,
            description: responseDescription,
            detail: responseCount > 0
                ? 'Analytics cards are now drawing from live survey data.'
                : 'As soon as one person submits, the dashboard populates automatically with live data.',
            ctaHref: hasDispatchedWave ? '/survey-waves' : '/survey-waves',
            ctaLabel: hasDispatchedWave ? 'Check Wave Status' : 'Finish Setup',
        },
    ];
});

const steps = computed(() => rawSteps.value.map((step, index) => ({
    ...step,
    isCurrent: firstIncompleteIndex.value === index,
    statusLabel: step.complete ? 'Done' : (firstIncompleteIndex.value === index ? 'Next Up' : 'Pending'),
})));

const completedSteps = computed(() => steps.value.filter((step) => step.complete).length);
const progressPercent = computed(() => {
    if (!steps.value.length) {
        return 0;
    }

    return Math.round((completedSteps.value / steps.value.length) * 100);
});

const handleCta = (step) => {
    if (!step?.ctaHref) {
        return;
    }

    emit('cta-click', step);
};
</script>

<style scoped>
.setup-card {
    background:
        radial-gradient(circle at top right, rgba(79, 70, 229, 0.08), transparent 34%),
        linear-gradient(180deg, #ffffff 0%, #fafbff 100%);
}

.setup-kicker {
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #4f46e5;
    margin-bottom: 0.75rem;
}

.setup-progress-chip {
    min-width: 112px;
    padding: 0.85rem 1rem;
    border-radius: 1rem;
    background: rgba(255, 255, 255, 0.8);
    border: 1px solid rgba(148, 163, 184, 0.18);
    text-align: center;
}

.setup-progress-value {
    font-family: 'Outfit', sans-serif;
    font-size: 1.5rem;
    font-weight: 700;
    line-height: 1;
    color: #0f172a;
}

.setup-progress-bar {
    height: 0.55rem;
    background: rgba(148, 163, 184, 0.18);
}

.setup-progress-bar .progress-bar {
    background: linear-gradient(90deg, #4f46e5, #0891b2);
}

.setup-step {
    border: 1px solid rgba(226, 232, 240, 0.9);
    border-radius: 1rem;
    padding: 1rem 1rem 1.05rem;
    background: rgba(255, 255, 255, 0.82);
}

.setup-step-current {
    border-color: rgba(79, 70, 229, 0.25);
    box-shadow: 0 12px 30px rgba(79, 70, 229, 0.08);
}

.setup-step-complete {
    background: rgba(248, 250, 252, 0.92);
}

.setup-step-icon {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.05rem;
    flex-shrink: 0;
}

.setup-step-icon-complete {
    background: rgba(16, 185, 129, 0.12);
    color: #047857;
}

.setup-step-icon-pending {
    background: rgba(79, 70, 229, 0.1);
    color: #4338ca;
}

.setup-status-badge {
    letter-spacing: 0.01em;
}
</style>
