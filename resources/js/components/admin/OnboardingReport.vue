<template>
    <div class="d-flex flex-column gap-4 fade-in">
        <div class="row g-3">
            <div v-for="card in summaryCards" :key="card.label" class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body p-4">
                        <div class="small text-uppercase fw-semibold text-secondary mb-2">{{ card.label }}</div>
                        <div class="display-6 fw-bold text-dark mb-1">{{ card.value }}</div>
                        <div class="small text-muted">{{ card.detail }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div
            class="card border-0 shadow-sm rounded-4 overflow-hidden"
            :class="systemStatus.has_live_survey ? 'border-start border-success border-4' : 'border-start border-warning border-4'"
        >
            <div class="card-body p-4 d-flex flex-wrap justify-content-between align-items-start gap-3">
                <div>
                    <div class="small text-uppercase fw-semibold text-secondary mb-2">Survey Content Status</div>
                    <h5 class="mb-2 fw-bold">
                        {{ systemStatus.has_live_survey ? 'A live survey version is available' : 'Manager onboarding is blocked by missing live survey content' }}
                    </h5>
                    <p class="small text-muted mb-2">
                        <template v-if="systemStatus.has_live_survey">
                            Workfit admin currently owns survey content. Managers can keep onboarding moving because one global live survey version is already active.
                        </template>
                        <template v-else>
                            Survey versions are shared globally. Until Workfit admin publishes one live survey version, managers cannot finish setup or send their first wave.
                        </template>
                    </p>
                    <div class="small text-muted">
                        <template v-if="systemStatus.live_survey">
                            Live version: v{{ systemStatus.live_survey.version }}{{ systemStatus.live_survey.title ? ` · ${systemStatus.live_survey.title}` : '' }}
                        </template>
                        <template v-else>
                            {{ systemStatus.blocking_companies_count }} company(s) are currently exposed to this global blocker.
                        </template>
                    </div>
                </div>

                <div class="d-flex flex-column align-items-start align-items-md-end gap-2">
                    <span class="badge rounded-pill px-3 py-2" :class="systemStatus.has_live_survey ? 'text-bg-success' : 'text-bg-warning'">
                        {{ systemStatus.has_live_survey ? 'Live Survey Ready' : 'Admin Action Required' }}
                    </span>
                    <a
                        v-if="!systemStatus.has_live_survey"
                        href="/admin/builder"
                        class="btn btn-sm btn-primary rounded-pill px-3"
                    >
                        Open Survey Builder
                    </a>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-12 col-xxl-7">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h5 class="mb-1 fw-bold">Stage Cohorts</h5>
                        <p class="small text-muted mb-0">Click a stage to focus the company queue on the bottleneck you want to work.</p>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-sm-6 col-xl-3">
                                <button
                                    type="button"
                                    class="w-100 border-0 rounded-4 stage-card text-start p-3"
                                    :class="{ selected: selectedStage === 'all' }"
                                    @click="setStageFilter('all')"
                                >
                                    <div class="small text-uppercase fw-semibold text-secondary mb-2">All Stages</div>
                                    <div class="h3 fw-bold mb-1">{{ summary.companies_total ?? 0 }}</div>
                                    <div class="small text-muted">Full onboarding scope</div>
                                </button>
                            </div>

                            <div v-for="stage in stageBreakdown" :key="stage.key" class="col-sm-6 col-xl-3">
                                <button
                                    type="button"
                                    class="w-100 border-0 rounded-4 stage-card text-start p-3"
                                    :class="[stageCardClass(stage.tone), { selected: selectedStage === stage.key }]"
                                    @click="setStageFilter(stage.key)"
                                >
                                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                        <div class="small text-uppercase fw-semibold">{{ stage.label }}</div>
                                        <span class="badge rounded-pill" :class="stageBadgeClass(stage.tone)">
                                            {{ stage.share }}%
                                        </span>
                                    </div>
                                    <div class="h3 fw-bold mb-1">{{ stage.count }}</div>
                                    <div class="small" :class="stageMetaClass(stage.tone)">
                                        {{ stage.alert_count }} open alert(s)
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xxl-5">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h5 class="mb-1 fw-bold">Plan Cohorts</h5>
                        <p class="small text-muted mb-0">Compare activation and billing readiness by plan tier.</p>
                    </div>
                    <div class="card-body p-4">
                        <div class="d-flex flex-column gap-3">
                            <div
                                v-for="plan in planBreakdown"
                                :key="plan.key"
                                class="border rounded-4 px-3 py-3 bg-light-subtle"
                            >
                                <div class="d-flex justify-content-between align-items-start gap-3 mb-2">
                                    <div>
                                        <div class="fw-semibold text-dark">{{ plan.label }}</div>
                                        <div class="small text-muted">{{ plan.count }} company(s)</div>
                                    </div>
                                    <span class="badge rounded-pill text-bg-dark">{{ plan.activation_rate }}% live data</span>
                                </div>
                                <div class="small text-muted">
                                    {{ plan.billing_ready_count }} billing-ready · {{ plan.live_data_count }} live data · {{ plan.alert_count }} alert(s)
                                </div>
                            </div>

                            <div v-if="planBreakdown.length === 0" class="text-center text-muted py-4">
                                No plan telemetry available yet.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white border-bottom py-3 px-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h5 class="mb-1 fw-bold">Action Queue</h5>
                    <p class="small text-muted mb-0">These companies need intervention to reach first data faster.</p>
                </div>
                <span class="badge rounded-pill px-3 py-2 text-bg-dark">{{ alerts.length }} open alert(s)</span>
            </div>
            <div class="list-group list-group-flush">
                <div v-for="alert in alerts" :key="`${alert.key}-${alert.company_id}`" class="list-group-item px-4 py-3">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                        <div class="flex-grow-1">
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                <span class="badge rounded-pill px-3 py-2" :class="severityClass(alert.severity)">
                                    {{ alert.severity_label }}
                                </span>
                                <span class="badge rounded-pill px-3 py-2" :class="stageClass(alert.stage?.tone)">
                                    {{ alert.stage?.label }}
                                </span>
                                <span class="small text-muted" v-if="alert.age_label">{{ alert.age_label }}</span>
                            </div>
                            <div class="fw-semibold text-dark">{{ alert.title }}</div>
                            <div class="small text-muted mb-2">
                                {{ alert.company_title }} · {{ alert.plan_label }} · {{ alert.billing_label }}
                            </div>
                            <div class="small text-dark mb-1">{{ alert.reason }}</div>
                            <div class="small text-muted">
                                {{ alert.recommended_action }}
                            </div>
                        </div>
                        <div class="small text-end text-muted">
                            <div>{{ alert.manager || 'Unassigned' }}</div>
                            <a
                                v-if="alert.manager_email"
                                class="text-decoration-none"
                                :href="mailtoHref(alert.manager_email)"
                            >
                                {{ alert.manager_email }}
                            </a>
                            <div class="mt-2">
                                {{ alert.checklist_views }} checklist · {{ alert.cta_clicks }} CTA
                            </div>
                            <div v-if="alert.last_event_name">
                                {{ prettifyEvent(alert.last_event_name) }}
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="alerts.length === 0" class="list-group-item px-4 py-5 text-center text-muted">
                    <i class="bi bi-check2-circle fs-1 d-block mb-2"></i>
                    No companies in this scope currently need intervention.
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white border-bottom py-3 px-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h5 class="mb-1 fw-bold">Activation By Company</h5>
                    <p class="small text-muted mb-0">Filter the queue by stage, then search for a specific company or manager.</p>
                </div>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <select
                        class="form-select form-select-sm rounded-pill px-3"
                        style="min-width: 180px;"
                        :value="selectedStage"
                        @change="setStageFilter($event.target.value)"
                    >
                        <option v-for="option in stageOptions" :key="option.key" :value="option.key">
                            {{ option.label }}
                        </option>
                    </select>
                    <div class="input-group" style="max-width: 320px;">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input
                            type="text"
                            class="form-control bg-light border-start-0 ps-0"
                            placeholder="Search companies or managers..."
                            :value="searchQuery"
                            @input="$emit('update:searchQuery', $event.target.value)"
                            @keyup.enter="$emit('search')"
                        >
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                    <tr>
                        <th class="ps-4 py-3 text-secondary text-uppercase small fw-bold">Company</th>
                        <th class="py-3 text-secondary text-uppercase small fw-bold">Stage</th>
                        <th class="py-3 text-secondary text-uppercase small fw-bold">Plan & Billing</th>
                        <th class="py-3 text-secondary text-uppercase small fw-bold">Started</th>
                        <th class="py-3 text-secondary text-uppercase small fw-bold">First Wave</th>
                        <th class="py-3 text-secondary text-uppercase small fw-bold">First Response</th>
                        <th class="py-3 text-secondary text-uppercase small fw-bold">Signals</th>
                        <th class="pe-4 py-3 text-secondary text-uppercase small fw-bold text-end">Last Event</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="company in companies.data" :key="company.id">
                        <td class="ps-4 py-3">
                            <div class="fw-bold text-dark">{{ company.title }}</div>
                            <div class="small text-muted">{{ company.manager_email }}</div>
                            <div class="small text-muted" v-if="company.alert">
                                <span class="fw-semibold">Alert:</span> {{ company.alert.title }}
                            </div>
                        </td>
                        <td class="py-3">
                            <span class="badge rounded-pill px-3 py-2" :class="stageClass(company.stage?.tone)">
                                {{ company.stage?.label || 'Unknown' }}
                            </span>
                        </td>
                        <td class="py-3">
                            <div class="small text-dark">{{ company.plan_label }}</div>
                            <div class="small text-muted">{{ company.billing_label }}</div>
                        </td>
                        <td class="py-3">
                            <div class="small text-dark">{{ formatDate(company.started_at) }}</div>
                        </td>
                        <td class="py-3">
                            <div class="small text-dark">{{ formatDate(company.first_wave_at) }}</div>
                            <div class="small text-muted" v-if="company.minutes_to_first_wave !== null">
                                {{ formatMinutes(company.minutes_to_first_wave) }}
                            </div>
                        </td>
                        <td class="py-3">
                            <div class="small text-dark">{{ formatDate(company.first_response_at) }}</div>
                            <div class="small text-muted" v-if="company.minutes_to_first_response !== null">
                                {{ formatMinutes(company.minutes_to_first_response) }}
                            </div>
                        </td>
                        <td class="py-3">
                            <div class="small text-dark">{{ company.checklist_views }} checklist view(s)</div>
                            <div class="small text-muted">{{ company.cta_clicks }} CTA/action click(s)</div>
                            <div class="small text-muted" v-if="company.recent_event_count">
                                {{ company.recent_event_count }} event(s) in 7d
                            </div>
                        </td>
                        <td class="pe-4 py-3 text-end">
                            <div class="small text-dark">{{ prettifyEvent(company.last_event_name) }}</div>
                            <div class="small text-muted">{{ formatDate(company.last_event_at) }}</div>
                        </td>
                    </tr>
                    <tr v-if="companies.data.length === 0">
                        <td colspan="8" class="text-center text-muted py-5">
                            <i class="bi bi-clipboard-data fs-1 d-block mb-2"></i>
                            No companies match the current onboarding scope.
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <div class="card-footer bg-white border-top d-flex justify-content-between align-items-center py-3 px-4" v-if="companies.last_page > 1">
                <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" :disabled="companies.current_page === 1" @click="$emit('page-change', companies.current_page - 1)">Previous</button>
                <span class="small text-muted">Page {{ companies.current_page }} of {{ companies.last_page }}</span>
                <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" :disabled="companies.current_page === companies.last_page" @click="$emit('page-change', companies.current_page + 1)">Next</button>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white border-bottom py-3 px-4">
                <h5 class="mb-1 fw-bold">Recent Onboarding Events</h5>
                <p class="small text-muted mb-0">Latest telemetry for the currently filtered onboarding scope.</p>
            </div>
            <div class="list-group list-group-flush">
                <div v-for="event in recentEvents" :key="event.id" class="list-group-item px-4 py-3">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                        <div>
                            <div class="fw-semibold text-dark">{{ event.company_title }}</div>
                            <div class="small text-muted">{{ prettifyEvent(event.name) }} · {{ event.context_surface }}</div>
                        </div>
                        <div class="small text-muted text-end">
                            {{ formatDate(event.created_at) }}
                        </div>
                    </div>
                </div>
                <div v-if="recentEvents.length === 0" class="list-group-item px-4 py-5 text-center text-muted">
                    <i class="bi bi-clock-history fs-1 d-block mb-2"></i>
                    No onboarding events recorded for this scope.
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    report: {
        type: Object,
        required: true,
    },
    searchQuery: {
        type: String,
        default: '',
    },
    stageFilter: {
        type: String,
        default: 'all',
    },
});

const emit = defineEmits(['update:searchQuery', 'update:stageFilter', 'search', 'stage-change', 'page-change']);

const summary = computed(() => props.report?.summary || {});
const systemStatus = computed(() => props.report?.system_status || {
    has_live_survey: false,
    live_survey: null,
    survey_content_owner: 'workfit_admin',
    blocking_companies_count: 0,
});
const filters = computed(() => props.report?.filters || { stage: 'all', stage_options: [] });
const companies = computed(() => props.report?.companies || { data: [], current_page: 1, last_page: 1 });
const stageBreakdown = computed(() => props.report?.stage_breakdown || []);
const planBreakdown = computed(() => props.report?.plan_breakdown || []);
const alerts = computed(() => props.report?.alerts || []);
const recentEvents = computed(() => props.report?.recent_events || []);
const stageOptions = computed(() => {
    if (filters.value.stage_options?.length) {
        return filters.value.stage_options;
    }

    return [
        { key: 'all', label: 'All Stages' },
        { key: 'dormant', label: 'Dormant' },
        { key: 'started', label: 'Started' },
        { key: 'wave_sent', label: 'Wave Sent' },
        { key: 'live_data', label: 'Live Data' },
    ];
});

const selectedStage = computed(() => props.stageFilter || filters.value.stage || 'all');

const summaryCards = computed(() => [
    {
        label: 'Companies In Scope',
        value: summary.value.companies_total ?? 0,
        detail: `${summary.value.companies_dormant ?? 0} dormant with no session`,
    },
    {
        label: 'First Wave Reached',
        value: summary.value.companies_dispatched ?? 0,
        detail: formatMinutes(summary.value.median_minutes_to_first_wave, 'Median time to first wave'),
    },
    {
        label: 'First Response Reached',
        value: summary.value.companies_responded ?? 0,
        detail: formatMinutes(summary.value.median_minutes_to_first_response, 'Median time to first response'),
    },
    {
        label: 'Open Alerts',
        value: summary.value.actionable_alerts ?? 0,
        detail: `${summary.value.high_priority_alerts ?? 0} high priority · ${summary.value.recent_event_count ?? 0} events in 7d`,
    },
]);

const stageClass = (tone) => {
    const classes = {
        success: 'bg-success-subtle text-success',
        primary: 'bg-primary-subtle text-primary',
        warning: 'bg-warning-subtle text-warning',
        secondary: 'bg-secondary-subtle text-secondary',
    };

    return classes[tone] || 'bg-light text-dark';
};

const stageCardClass = (tone) => {
    const classes = {
        success: 'stage-card-success',
        primary: 'stage-card-primary',
        warning: 'stage-card-warning',
        secondary: 'stage-card-secondary',
    };

    return classes[tone] || '';
};

const stageBadgeClass = (tone) => {
    const classes = {
        success: 'text-bg-success',
        primary: 'text-bg-primary',
        warning: 'text-bg-warning',
        secondary: 'text-bg-secondary',
    };

    return classes[tone] || 'text-bg-dark';
};

const stageMetaClass = (tone) => {
    const classes = {
        success: 'text-success-emphasis',
        primary: 'text-primary-emphasis',
        warning: 'text-warning-emphasis',
        secondary: 'text-secondary-emphasis',
    };

    return classes[tone] || 'text-muted';
};

const severityClass = (severity) => {
    const classes = {
        high: 'text-bg-danger',
        medium: 'text-bg-warning',
        low: 'text-bg-secondary',
    };

    return classes[severity] || 'text-bg-dark';
};

const setStageFilter = (value) => {
    emit('update:stageFilter', value);
    emit('stage-change');
};

const formatDate = (value) => {
    if (!value) return 'Not yet';
    return new Date(value).toLocaleString();
};

function formatMinutes(value, prefix = null) {
    if (value === null || value === undefined) {
        return prefix ? `${prefix}: n/a` : 'n/a';
    }

    const minutes = Number(value);
    if (!Number.isFinite(minutes)) {
        return prefix ? `${prefix}: n/a` : 'n/a';
    }

    if (minutes < 60) {
        const label = `${minutes} min`;
        return prefix ? `${prefix}: ${label}` : label;
    }

    const hours = Math.round((minutes / 60) * 10) / 10;
    const label = `${hours} hr`;
    return prefix ? `${prefix}: ${label}` : label;
}

const prettifyEvent = (name) => {
    if (!name) return 'No events yet';

    return name
        .split('_')
        .map((segment) => segment.charAt(0).toUpperCase() + segment.slice(1))
        .join(' ');
};

const mailtoHref = (email) => `mailto:${email}`;
</script>

<style scoped>
.fade-in {
    animation: fadeIn 0.3s ease-in-out;
}

.stage-card {
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.18);
    transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
}

.stage-card:hover {
    transform: translateY(-1px);
    box-shadow: inset 0 0 0 1px rgba(59, 130, 246, 0.2), 0 14px 28px rgba(15, 23, 42, 0.08);
}

.stage-card.selected {
    box-shadow: inset 0 0 0 2px rgba(15, 23, 42, 0.8), 0 16px 32px rgba(15, 23, 42, 0.08);
}

.stage-card-success {
    background: linear-gradient(180deg, #f0fdf4 0%, #dcfce7 100%);
}

.stage-card-primary {
    background: linear-gradient(180deg, #eff6ff 0%, #dbeafe 100%);
}

.stage-card-warning {
    background: linear-gradient(180deg, #fff7ed 0%, #ffedd5 100%);
}

.stage-card-secondary {
    background: linear-gradient(180deg, #f8fafc 0%, #e2e8f0 100%);
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
