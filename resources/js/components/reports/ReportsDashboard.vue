<template>
    <div class="row g-4">
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
                    <div v-else-if="trendData" class="chart-container" style="position: relative; height: 400px;">
                        <trend-chart :data="trendData" />
                    </div>
                    <div v-else class="text-center py-5 text-muted">
                        <div class="bg-light rounded-circle p-4 mb-3 d-inline-block">
                            <i class="bi bi-graph-up text-secondary display-4"></i>
                        </div>
                        <p>No data available for the selected period.</p>
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
                    <div v-else-if="comparisonData" class="chart-container" style="position: relative; height: 400px;">
                        <comparison-chart :data="comparisonData" />
                    </div>
                    <div v-else class="text-center py-5 text-muted">
                        <div class="bg-light rounded-circle p-4 mb-3 d-inline-block">
                            <i class="bi bi-bar-chart text-secondary display-4"></i>
                        </div>
                        <p>No comparison data available.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';
import TrendChart from './TrendChart.vue';
import ComparisonChart from './ComparisonChart.vue';

const activeTab = ref('trends');
const loading = ref(false);

// Trends State
const trendMetric = ref('engagement');
const trendData = ref(null);

// Comparison State
const comparisonDimension = ref('department');
const comparisonData = ref(null);

const fetchTrends = async () => {
    loading.value = true;
    try {
        const { data } = await axios.get(`/reports/trends?metric=${trendMetric.value}`);
        trendData.value = data;
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
};

const fetchComparison = async () => {
    loading.value = true;
    try {
        const { data } = await axios.get(`/reports/comparison?dimension=${comparisonDimension.value}`);
        comparisonData.value = data;
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
};

watch(activeTab, (newTab) => {
    if (newTab === 'trends' && !trendData.value) fetchTrends();
    if (newTab === 'comparison' && !comparisonData.value) fetchComparison();
});

onMounted(() => {
    fetchTrends();
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
