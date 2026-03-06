<template>
    <div class="analytics-dashboard">
        <!-- Header & Filters -->
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <h1 class="h3 mb-0 text-gray-800">Dashboard Analytics</h1>

            <div class="d-flex flex-wrap gap-2 align-items-center">
                <select
                    v-if="isWorkfitAdmin"
                    v-model="selectedCompanyIdRaw"
                    class="form-select form-select-sm"
                    style="width: 240px;"
                >
                    <option value="">Select Company</option>
                    <option v-for="company in companies" :key="company.id" :value="String(company.id)">
                        {{ company.title }}
                    </option>
                </select>

                <select
                    v-model="filters.department"
                    class="form-select form-select-sm"
                    style="width: 200px;"
                    :disabled="!hasCompanyContext"
                >
                    <option value="">All Departments</option>
                    <option v-for="dept in options.departments" :key="dept.department" :value="dept.department">
                        {{ dept.department }}
                    </option>
                </select>

                <select
                    v-model="filters.team"
                    class="form-select form-select-sm"
                    style="width: 200px;"
                    :disabled="!hasCompanyContext"
                >
                    <option value="">All Teams</option>
                    <option v-for="lead in options.teamleads" :key="lead.name" :value="lead.name">
                        {{ lead.name }}
                    </option>
                </select>

                <select
                    v-model="filters.wave"
                    class="form-select form-select-sm"
                    style="width: 200px;"
                    :disabled="!hasCompanyContext"
                >
                    <option value="">Latest Wave</option>
                    <option v-for="(label, key) in options.waves" :key="key" :value="key">
                        {{ label }}
                    </option>
                </select>

                <button class="btn btn-sm btn-outline-secondary" @click="resetFilters" :disabled="isLoading || !hasCompanyContext">
                    Reset
                </button>
            </div>
        </div>

        <div v-if="requiresCompanySelection" class="alert alert-info">
            Select a company to load analytics.
        </div>

        <div v-else-if="showNoCompanyContext" class="alert alert-warning">
            No company context found for your account. Contact an administrator to assign a company.
        </div>

        <!-- Loading State -->
        <div v-else-if="isLoading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 text-muted">Loading analytics data...</p>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="alert alert-danger">
            {{ error }}
            <button class="btn btn-sm btn-link" @click="fetchData">Try Again</button>
        </div>

        <div v-else-if="!hasAnalyticsData" class="row g-4">
            <div class="col-xl-8">
                <setup-checklist :setup="setup" :user="user" @cta-click="handleChecklistCtaClick" />
            </div>
            <div class="col-xl-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="small text-uppercase fw-semibold text-primary mb-2">Activation Snapshot</div>
                        <h4 class="mb-2">{{ emptyState.title }}</h4>
                        <p class="text-muted mb-4">{{ emptyState.body }}</p>

                        <div class="d-grid gap-2 mb-4">
                            <a
                                v-if="activationPrimaryAction"
                                :href="activationPrimaryAction.href"
                                class="btn btn-primary"
                                @click.prevent="handleActivationActionClick(activationPrimaryAction, 'primary')"
                            >
                                {{ activationPrimaryAction.label }}
                            </a>
                            <a
                                v-if="activationSecondaryAction"
                                :href="activationSecondaryAction.href"
                                class="btn btn-outline-secondary"
                                @click.prevent="handleActivationActionClick(activationSecondaryAction, 'secondary')"
                            >
                                {{ activationSecondaryAction.label }}
                            </a>
                        </div>

                        <div class="border rounded-4 p-3 bg-light-subtle">
                            <div class="small text-uppercase fw-semibold text-secondary mb-3">Current company state</div>
                            <div class="d-flex flex-column gap-3 small">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">Recipients ready</span>
                                    <strong>{{ setup.recipient_count ?? 0 }}</strong>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">Departments</span>
                                    <strong>{{ setup.department_count ?? 0 }}</strong>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">Live survey</span>
                                    <strong>{{ setup.live_survey?.version ? `v${setup.live_survey.version}` : 'Missing' }}</strong>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">Dispatched waves</span>
                                    <strong>{{ setup.dispatched_wave_count ?? 0 }}</strong>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">Completed responses</span>
                                    <strong>{{ setup.response_count ?? 0 }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dashboard Content -->
        <div v-else class="dashboard-content">
            <!-- Top Row: Temperature & Indicators -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-header bg-white py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Temperature Index</h6>
                        </div>
                        <div class="card-body d-flex align-items-center justify-content-center">
                            <temperature-gauge :score="data.temperature" />
                        </div>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="card h-100 shadow-sm">
                        <div class="card-header bg-white py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Key Indicators</h6>
                        </div>
                        <div class="card-body">
                            <indicator-list :indicators="data.indicators || []" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Middle Row: Gap Chart & Culture -->
            <div class="row mb-4">
                <div class="col-lg-8">
                    <div class="card h-100 shadow-sm">
                        <div class="card-header bg-white py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Gap Analysis</h6>
                        </div>
                        <div class="card-body">
                            <gap-chart :items="data.gap_chart || []" />
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-header bg-white py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Team Culture Pulse</h6>
                        </div>
                        <div class="card-body">
                            <team-culture-pulse
                                v-if="data.team_culture"
                                :score="data.team_culture.score"
                                :positive="data.team_culture.positive"
                                :negative="data.team_culture.negative"
                                :positive-items="getPositiveItems(data.team_culture.items)"
                                :negative-items="getNegativeItems(data.team_culture.items)"
                            />
                            <div v-else class="text-center text-muted py-4">
                                No culture data available
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Row: Scatter & Impact -->
            <div class="row mb-4">
                <div class="col-lg-6">
                    <div class="card h-100 shadow-sm">
                        <div class="card-header bg-white py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Team Scatter Plot</h6>
                        </div>
                        <div class="card-body">
                            <team-scatter :points="data.team_scatter || []" />
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card h-100 shadow-sm">
                        <div class="card-header bg-white py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Impact Snapshot</h6>
                        </div>
                        <div class="card-body">
                            <impact-snapshot
                                v-if="data.impact"
                                :positive="data.impact.positive"
                                :importance="data.impact.importance"
                                :desire="data.impact.desire"
                            />
                            <div v-else class="text-center text-muted py-4">
                                No impact data available
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
import { debounce } from 'lodash';
import { useAnalyticsApi } from '../../composables/useAnalyticsApi';
import {
    ensureOnboardingSessionStarted,
    trackOnboardingEvent,
    trackOnboardingEventOnce,
} from '../../lib/onboardingTelemetry';
import GapChart from '../dashboard/GapChart.vue';
import ImpactSnapshot from '../dashboard/ImpactSnapshot.vue';
import IndicatorList from '../dashboard/IndicatorList.vue';
import SetupChecklist from './SetupChecklist.vue';
import TeamCulturePulse from '../dashboard/TeamCulturePulse.vue';
import TeamScatter from '../dashboard/TeamScatter.vue';
import TemperatureGauge from '../dashboard/TemperatureGauge.vue';

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
    initialCompanyId: {
        type: [Number, String],
        default: null,
    },
    companies: {
        type: Array,
        default: () => [],
    },
});

const { getDashboardData } = useAnalyticsApi();

const normalizeCompanyId = (value) => {
    const parsed = Number(value);
    return Number.isFinite(parsed) && parsed > 0 ? parsed : null;
};

const inferredCompanyId = computed(() => normalizeCompanyId(props.initialCompanyId) ?? normalizeCompanyId(props.user?.company_id));
const selectedCompanyIdRaw = ref(inferredCompanyId.value ? String(inferredCompanyId.value) : '');
const user = computed(() => props.user);

const isWorkfitAdmin = computed(() => Number(props.user?.is_admin ?? 0) === 1 || Number(props.user?.role ?? 0) === 0);
const selectedCompanyId = computed(() => normalizeCompanyId(selectedCompanyIdRaw.value));
const hasCompanyContext = computed(() => selectedCompanyId.value !== null);
const requiresCompanySelection = computed(() => isWorkfitAdmin.value && !hasCompanyContext.value);
const showNoCompanyContext = computed(() => !isWorkfitAdmin.value && !hasCompanyContext.value);
const role = computed(() => Number(props.user?.role ?? 0));
const userSegment = computed(() => {
    if (isWorkfitAdmin.value) return 'expert';
    if (role.value === 1) return 'novice';
    return 'intermediate';
});

const isLoading = ref(false);
const error = ref(null);
const data = ref({});
const setup = ref({});

const options = reactive({
    departments: [],
    teamleads: [],
    waves: [],
    exist_departments: [],
});

const filters = reactive({
    department: '',
    team: '',
    wave: '',
});

const clearFilterOptions = () => {
    options.departments = [];
    options.teamleads = [];
    options.waves = [];
    options.exist_departments = [];
};

const hasAnalyticsData = computed(() => {
    const payload = data.value || {};

    return Boolean(
        (Array.isArray(payload.indicators) && payload.indicators.length > 0) ||
        (Array.isArray(payload.gap_chart) && payload.gap_chart.length > 0) ||
        (Array.isArray(payload.team_scatter) && payload.team_scatter.length > 0) ||
        payload.temperature !== null && payload.temperature !== undefined ||
        payload.weighted_indicator !== null && payload.weighted_indicator !== undefined ||
        (payload.team_culture && Object.keys(payload.team_culture).length > 0) ||
        (payload.impact && Object.keys(payload.impact).length > 0)
    );
});

const surveyContentAdminOwnedHandoff = computed(() => {
    const currentSetup = setup.value || {};

    return !currentSetup.has_live_survey
        && !currentSetup.can_manage_survey_content
        && Number(currentSetup.recipient_count ?? 0) > 0
        && !!currentSetup.billing_allows_scheduling;
});

const emptyState = computed(() => {
    if (isWorkfitAdmin.value) {
        return {
            title: 'Pick a company or prepare one for demo data',
            body: 'This dashboard is ready, but there are no survey responses for the current company selection yet.',
            primaryHref: '/admin/builder',
            primaryLabel: 'Open Survey Builder',
            secondaryHref: '/admin',
            secondaryLabel: 'Open Admin Panel',
        };
    }

    if (role.value === 1) {
        if (surveyContentAdminOwnedHandoff.value) {
            return {
                title: 'Workfit admin still needs to activate the live survey',
                body: 'Your team and billing can be ready, but survey content is shared globally in Empulse. A Workfit admin must publish the live survey before you can send the first wave.',
                primaryHref: '/contact',
                primaryLabel: 'Contact Workfit Admin',
                secondaryHref: '/surveys/manage',
                secondaryLabel: 'Review Survey Status',
            };
        }

        return {
            title: 'Launch your first survey wave',
            body: 'Import teammates, confirm billing, and send a wave once the live survey is ready. Analytics will fill in as soon as responses arrive.',
            primaryHref: '/survey-waves',
            primaryLabel: 'Create Wave',
            secondaryHref: '/team/manage',
            secondaryLabel: 'Manage Team',
        };
    }

    return {
        title: 'Waiting for your team’s first responses',
        body: 'This view is ready for company analytics, but a manager still needs to dispatch a survey wave before the charts can populate.',
        primaryHref: '/team/manage',
        primaryLabel: 'View Team',
        secondaryHref: '',
        secondaryLabel: '',
    };
});

const activationPrimaryAction = computed(() => {
    const currentSetup = setup.value || {};

    if (!Number(currentSetup.recipient_count ?? 0)) {
        return {
            href: '/team/manage',
            label: 'Add Team Members',
        };
    }

    if (!currentSetup.billing_allows_scheduling) {
        if (role.value === 1) {
            return {
                href: '/account/billing',
                label: 'Open Billing',
            };
        }

        if (isWorkfitAdmin.value) {
            return {
                href: '/admin',
                label: 'Open Admin Panel',
            };
        }
    }

    if (!currentSetup.has_live_survey) {
        return currentSetup.can_manage_survey_content
            ? {
                href: '/admin/builder',
                label: 'Open Survey Builder',
            }
            : {
                href: '/contact',
                label: 'Contact Workfit Admin',
                handoff: true,
            };
    }

    if (!currentSetup.has_dispatched_wave) {
        return {
            href: '/survey-waves',
            label: 'Open Survey Waves',
        };
    }

    if (!Number(currentSetup.response_count ?? 0)) {
        return {
            href: '/survey-waves',
            label: 'Check Wave Status',
        };
    }

    if (!emptyState.value.primaryHref) {
        return null;
    }

    return {
        href: emptyState.value.primaryHref,
        label: emptyState.value.primaryLabel,
    };
});

const activationSecondaryAction = computed(() => {
    if (!emptyState.value.secondaryHref) {
        return null;
    }

    if (emptyState.value.secondaryHref === activationPrimaryAction.value?.href) {
        return null;
    }

    return {
        href: emptyState.value.secondaryHref,
        label: emptyState.value.secondaryLabel,
        handoff: surveyContentAdminOwnedHandoff.value && emptyState.value.secondaryHref === '/surveys/manage',
    };
});

const trackDashboardSession = async () => {
    if (!hasCompanyContext.value) {
        return;
    }

    await ensureOnboardingSessionStarted({
        companyId: selectedCompanyId.value,
        contextSurface: 'dashboard.analytics',
        taskId: 'company_activation',
        userSegment: userSegment.value,
        guidanceLevel: 'light',
    });
};

const trackChecklistView = async () => {
    if (!hasCompanyContext.value || hasAnalyticsData.value) {
        return;
    }

    await trackOnboardingEventOnce({
        onceId: 'dashboard-checklist-view',
        companyId: selectedCompanyId.value,
        name: 'onboarding_checklist_viewed',
        contextSurface: 'dashboard.analytics',
        taskId: 'company_activation',
        userSegment: userSegment.value,
        guidanceLevel: 'light',
        properties: {
            recipient_count: setup.value?.recipient_count ?? 0,
            response_count: setup.value?.response_count ?? 0,
            has_live_survey: !!setup.value?.has_live_survey,
            billing_allows_scheduling: !!setup.value?.billing_allows_scheduling,
        },
    });
};

const trackSurveyActivationHandoffView = async () => {
    if (!hasCompanyContext.value || !surveyContentAdminOwnedHandoff.value) {
        return;
    }

    await trackOnboardingEventOnce({
        onceId: 'survey-activation-handoff-viewed',
        companyId: selectedCompanyId.value,
        name: 'survey_activation_handoff_viewed',
        contextSurface: 'dashboard.analytics',
        taskId: 'survey_activation',
        userSegment: userSegment.value,
        guidanceLevel: 'light',
        properties: {
            survey_content_owner: setup.value?.survey_content_owner ?? 'workfit_admin',
            can_manage_survey_content: !!setup.value?.can_manage_survey_content,
            has_live_survey: !!setup.value?.has_live_survey,
        },
    });
};

const trackSurveyActivationHandoffClick = async (destination, origin) => {
    await trackOnboardingEvent({
        companyId: selectedCompanyId.value,
        name: 'survey_activation_handoff_clicked',
        contextSurface: 'dashboard.analytics',
        taskId: 'survey_activation',
        userSegment: userSegment.value,
        guidanceLevel: 'light',
        properties: {
            destination,
            origin,
            survey_content_owner: setup.value?.survey_content_owner ?? 'workfit_admin',
        },
        useKeepalive: true,
    });
};

const handleChecklistCtaClick = async (step) => {
    const isSurveyHandoff = step?.id === 'survey' && surveyContentAdminOwnedHandoff.value;

    if (isSurveyHandoff) {
        await trackSurveyActivationHandoffClick(step?.ctaHref ?? null, 'checklist');
    }

    await trackOnboardingEvent({
        companyId: selectedCompanyId.value,
        name: 'onboarding_step_cta_clicked',
        contextSurface: 'dashboard.analytics',
        taskId: step?.id || 'company_activation',
        userSegment: userSegment.value,
        guidanceLevel: 'light',
        properties: {
            destination: step?.ctaHref ?? null,
            label: step?.ctaLabel ?? null,
            status: step?.statusLabel ?? null,
        },
        useKeepalive: true,
    });

    if (step?.ctaHref) {
        window.location.href = step.ctaHref;
    }
};

const handleActivationActionClick = async (action, position) => {
    if (action?.handoff) {
        await trackSurveyActivationHandoffClick(action?.href ?? null, `activation_${position}`);
    }

    await trackOnboardingEvent({
        companyId: selectedCompanyId.value,
        name: 'onboarding_step_cta_clicked',
        contextSurface: 'dashboard.analytics',
        taskId: position === 'primary' ? 'activation_primary' : 'activation_secondary',
        userSegment: userSegment.value,
        guidanceLevel: 'light',
        properties: {
            destination: action?.href ?? null,
            label: action?.label ?? null,
            position,
        },
        useKeepalive: true,
    });

    if (action?.href) {
        window.location.href = action.href;
    }
};

let requestCounter = 0;

const resetFilters = () => {
    filters.department = '';
    filters.team = '';
    filters.wave = '';
};

const fetchData = async () => {
    if (!hasCompanyContext.value) {
        isLoading.value = false;
        error.value = null;
        data.value = {};
        setup.value = {};
        clearFilterOptions();
        return;
    }

    isLoading.value = true;
    error.value = null;
    const requestId = ++requestCounter;

    try {
        const response = await getDashboardData({
            company_id: selectedCompanyId.value,
            department: filters.department,
            team: filters.team,
            wave: filters.wave,
        });

        if (requestId !== requestCounter) {
            return;
        }

        data.value = response.data || {};
        setup.value = response.setup || {};
        options.departments = response.filters?.departments || [];
        options.teamleads = response.filters?.teamleads || [];
        options.waves = response.filters?.waves || [];
        options.exist_departments = response.filters?.exist_departments || [];
    } catch (err) {
        if (requestId !== requestCounter) {
            return;
        }

        console.error('Error fetching analytics:', err);
        error.value = err?.response?.data?.message || 'Failed to load dashboard data. Please try again.';
    } finally {
        if (requestId !== requestCounter) {
            return;
        }

        isLoading.value = false;
    }

    await trackDashboardSession();
    await trackChecklistView();
    await trackSurveyActivationHandoffView();
};

const debouncedFetch = debounce(fetchData, 400);

const getPositiveItems = (items) => {
    if (!items) return [];
    return items.filter((item) => item.polarity === 'positive').slice(0, 3);
};

const getNegativeItems = (items) => {
    if (!items) return [];
    return items.filter((item) => item.polarity === 'negative').slice(0, 3);
};

watch(filters, () => {
    if (!hasCompanyContext.value) {
        return;
    }

    debouncedFetch();
}, { deep: true });

watch(selectedCompanyIdRaw, () => {
    requestCounter += 1;
    debouncedFetch.cancel();
    resetFilters();
    data.value = {};
    setup.value = {};
    clearFilterOptions();

    if (hasCompanyContext.value) {
        fetchData();
    } else {
        isLoading.value = false;
        error.value = null;
    }
});

onMounted(() => {
    if (hasCompanyContext.value) {
        fetchData();
    }
});
</script>

<style scoped>
.card {
    border: none;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15) !important;
}
.card-header {
    background-color: #f8f9fc;
    border-bottom: 1px solid #e3e6f0;
}
</style>
