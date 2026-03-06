<template>
    <div>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <h1 class="h3 mb-0 text-gray-800">Reports</h1>

            <div class="d-flex gap-2 align-items-center">
                <select
                    v-if="isWorkfitAdmin"
                    v-model="selectedCompanyIdRaw"
                    class="form-select form-select-sm"
                    style="width: 260px;"
                >
                    <option value="">Select Company</option>
                    <option v-for="company in companies" :key="company.id" :value="String(company.id)">
                        {{ company.title }}
                    </option>
                </select>
            </div>
        </div>

        <div v-if="requiresCompanySelection" class="alert alert-info">
            Select a company to view reports.
        </div>

        <div v-else-if="showNoCompanyContext" class="alert alert-warning">
            No company context found for your account. Contact an administrator to assign a company.
        </div>

        <div v-else-if="globalError" class="alert alert-danger">
            {{ globalError }}
            <button class="btn btn-sm btn-link" @click="refreshActiveTab">Try Again</button>
        </div>

        <div v-else class="row g-4">
            <!-- Sidebar -->
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-3">
                        <h6 class="text-secondary text-uppercase small fw-bold mb-3 px-3">Reports</h6>
                        <div class="d-flex flex-column gap-2">
                            <button class="btn text-start p-3 rounded-3 d-flex align-items-center transition-all"
                                    :class="activeTab === 'trends' ? 'btn-primary shadow-sm' : 'btn-light bg-white hover-bg text-dark border-0'"
                                    @click="activeTab = 'trends'">
                                <div class="rounded-circle p-2 me-3 d-flex align-items-center justify-content-center"
                                     :class="activeTab === 'trends' ? 'bg-white bg-opacity-25' : 'bg-light'">
                                    <i class="bi bi-graph-up-arrow fs-5"></i>
                                </div>
                                <div>
                                    <div class="fw-bold">Trends</div>
                                    <div class="small opacity-75">Historical performance</div>
                                </div>
                            </button>

                            <button class="btn text-start p-3 rounded-3 d-flex align-items-center transition-all"
                                    :class="activeTab === 'comparison' ? 'btn-primary shadow-sm' : 'btn-light bg-white hover-bg text-dark border-0'"
                                    @click="activeTab = 'comparison'">
                                <div class="rounded-circle p-2 me-3 d-flex align-items-center justify-content-center"
                                     :class="activeTab === 'comparison' ? 'bg-white bg-opacity-25' : 'bg-light'">
                                    <i class="bi bi-bar-chart-fill fs-5"></i>
                                </div>
                                <div>
                                    <div class="fw-bold">Comparisons</div>
                                    <div class="small opacity-75">Team vs Team analysis</div>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9">
                <!-- Trends View -->
                <div v-if="activeTab === 'trends'" class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1 fw-bold">Historical Trends</h5>
                            <p class="text-muted small mb-0">Track engagement and culture scores over time.</p>
                        </div>
                        <div class="bg-light p-1 rounded-pill d-flex align-items-center border">
                            <button class="btn btn-sm rounded-pill px-3 fw-semibold transition-all"
                                    :class="trendMetric === 'engagement' ? 'btn-white shadow-sm text-primary' : 'text-muted'"
                                    @click="trendMetric = 'engagement'; fetchTrends()">
                                Engagement
                            </button>
                            <button class="btn btn-sm rounded-pill px-3 fw-semibold transition-all"
                                    :class="trendMetric === 'culture' ? 'btn-white shadow-sm text-primary' : 'text-muted'"
                                    @click="trendMetric = 'culture'; fetchTrends()">
                                Culture
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div v-if="loading" class="text-center py-5">
                            <div class="spinner-border text-primary" role="status"></div>
                        </div>
                        <div v-else-if="hasTrendData" class="chart-container" style="position: relative; height: 400px;">
                            <trend-chart :data="trendData" />
                        </div>
                        <div v-else class="text-center py-5 text-muted">
                            <div class="bg-light rounded-circle p-4 mb-3 d-inline-block">
                                <i class="bi bi-graph-up text-secondary display-4"></i>
                            </div>
                            <p>No completed survey waves are available yet. Trends will appear once your team has historical responses.</p>
                        </div>
                    </div>
                </div>

                <!-- Comparison View -->
                <div v-if="activeTab === 'comparison'" class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1 fw-bold">Comparative Analysis</h5>
                            <p class="text-muted small mb-0">Compare performance across different groups.</p>
                        </div>
                        <div class="d-flex gap-2">
                            <select class="form-select form-select-sm bg-light border-0 fw-semibold" v-model="comparisonWave" @change="fetchComparison">
                                <option value="">Latest available wave</option>
                                <option v-for="(label, key) in waveOptions" :key="key" :value="key">
                                    {{ label }}
                                </option>
                            </select>
                            <select class="form-select form-select-sm bg-light border-0 fw-semibold" v-model="comparisonDimension" @change="fetchComparison">
                                <option value="department">By Department</option>
                                <option value="team">By Team (Supervisor)</option>
                            </select>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div v-if="loading" class="text-center py-5">
                            <div class="spinner-border text-primary" role="status"></div>
                        </div>
                        <div v-else-if="hasComparisonData" class="chart-container" style="position: relative; height: 400px;">
                            <comparison-chart :data="comparisonData" />
                        </div>
                        <div v-else class="text-center py-5 text-muted">
                            <div class="bg-light rounded-circle p-4 mb-3 d-inline-block">
                                <i class="bi bi-bar-chart text-secondary display-4"></i>
                            </div>
                            <p>{{ comparisonEmptyMessage }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useReportsApi } from '../../composables/useReportsApi';
import ComparisonChart from './ComparisonChart.vue';
import TrendChart from './TrendChart.vue';

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

const { getTrends, getComparison, getOptions } = useReportsApi();

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

const activeTab = ref('trends');
const loading = ref(false);
const globalError = ref(null);

// Trends State
const trendMetric = ref('engagement');
const trendData = ref(null);

// Comparison State
const comparisonDimension = ref('department');
const comparisonWave = ref('');
const comparisonData = ref(null);
const waveOptions = ref({});
let requestCounter = 0;

const hasTrendData = computed(() => Array.isArray(trendData.value?.labels) && trendData.value.labels.length > 0);
const hasComparisonData = computed(() => Array.isArray(comparisonData.value?.labels) && comparisonData.value.labels.length > 0);
const comparisonEmptyMessage = computed(() => {
    if (!Object.keys(waveOptions.value).length) {
        return 'Create and complete a survey wave to unlock comparison reports.';
    }

    if (comparisonWave.value) {
        return 'No comparison data is available for the selected wave yet.';
    }

    return 'No comparison data is available yet.';
});

const fetchOptions = async () => {
    if (!hasCompanyContext.value) {
        waveOptions.value = {};
        comparisonWave.value = '';
        return;
    }

    try {
        const result = await getOptions(selectedCompanyId.value);
        waveOptions.value = result?.waves || {};
    } catch (error) {
        console.error(error);
        waveOptions.value = {};
    }
};

const fetchTrends = async () => {
    if (!hasCompanyContext.value) {
        return;
    }

    loading.value = true;
    globalError.value = null;
    const requestId = ++requestCounter;

    try {
        const result = await getTrends(trendMetric.value, selectedCompanyId.value);
        if (requestId !== requestCounter) {
            return;
        }

        trendData.value = result;
    } catch (error) {
        if (requestId !== requestCounter) {
            return;
        }

        console.error(error);
        globalError.value = error?.response?.data?.message || 'Failed to load trends.';
    } finally {
        if (requestId !== requestCounter) {
            return;
        }

        loading.value = false;
    }
};

const fetchComparison = async () => {
    if (!hasCompanyContext.value) {
        return;
    }

    loading.value = true;
    globalError.value = null;
    const requestId = ++requestCounter;

    try {
        const result = await getComparison(comparisonDimension.value, comparisonWave.value || null, selectedCompanyId.value);
        if (requestId !== requestCounter) {
            return;
        }

        comparisonData.value = result;
    } catch (error) {
        if (requestId !== requestCounter) {
            return;
        }

        if (error?.response?.status === 404) {
            comparisonData.value = { labels: [], datasets: [] };
            globalError.value = null;
            return;
        }

        console.error(error);
        globalError.value = error?.response?.data?.message || 'Failed to load comparison report.';
    } finally {
        if (requestId !== requestCounter) {
            return;
        }

        loading.value = false;
    }
};

const refreshActiveTab = async () => {
    if (activeTab.value === 'comparison') {
        await fetchComparison();
        return;
    }

    await fetchTrends();
};

watch(activeTab, (newTab) => {
    if (!hasCompanyContext.value) {
        return;
    }

    if (newTab === 'trends' && !trendData.value) {
        fetchTrends();
    }

    if (newTab === 'comparison' && !comparisonData.value) {
        fetchComparison();
    }
});

watch(selectedCompanyIdRaw, () => {
    requestCounter += 1;
    trendData.value = null;
    comparisonData.value = null;
    globalError.value = null;
    comparisonWave.value = '';
    waveOptions.value = {};

    if (!hasCompanyContext.value) {
        return;
    }

    fetchOptions();
    refreshActiveTab();
});

onMounted(() => {
    if (hasCompanyContext.value) {
        fetchOptions();
        fetchTrends();
    }
});
</script>

<style scoped>
.hover-bg:hover {
    background-color: #f8f9fa !important;
}
.transition-all {
    transition: all 0.2s ease;
}
.btn-white {
    background-color: #fff;
    border: 1px solid #e9ecef;
}
.btn-white:hover {
    background-color: #f8f9fa;
}
</style>
