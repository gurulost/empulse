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
                            <temperature-gauge :score="data.weighted_indicator" />
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
import GapChart from '../dashboard/GapChart.vue';
import ImpactSnapshot from '../dashboard/ImpactSnapshot.vue';
import IndicatorList from '../dashboard/IndicatorList.vue';
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

const isWorkfitAdmin = computed(() => Number(props.user?.is_admin ?? 0) === 1 || Number(props.user?.role ?? 0) === 0);
const selectedCompanyId = computed(() => normalizeCompanyId(selectedCompanyIdRaw.value));
const hasCompanyContext = computed(() => selectedCompanyId.value !== null);
const requiresCompanySelection = computed(() => isWorkfitAdmin.value && !hasCompanyContext.value);
const showNoCompanyContext = computed(() => !isWorkfitAdmin.value && !hasCompanyContext.value);

const isLoading = ref(false);
const error = ref(null);
const data = ref({});

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
