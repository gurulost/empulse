<template>
    <div class="analytics-dashboard">
        <!-- Header & Filters -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">Dashboard Analytics</h1>
            
            <div class="d-flex gap-2">
                <select v-model="filters.department" class="form-select form-select-sm" style="width: 200px;">
                    <option value="">All Departments</option>
                    <option v-for="dept in options.departments" :key="dept.department" :value="dept.department">
                        {{ dept.department }}
                    </option>
                </select>

                <select v-model="filters.team" class="form-select form-select-sm" style="width: 200px;">
                    <option value="">All Teams</option>
                    <option v-for="lead in options.teamleads" :key="lead.name" :value="lead.name">
                        {{ lead.name }}
                    </option>
                </select>

                <select v-model="filters.wave" class="form-select form-select-sm" style="width: 200px;">
                    <option value="">Latest Wave</option>
                    <option v-for="(label, key) in options.waves" :key="key" :value="key">
                        {{ label }}
                    </option>
                </select>
                
                <button class="btn btn-sm btn-outline-secondary" @click="resetFilters" :disabled="isLoading">
                    Reset
                </button>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="isLoading" class="text-center py-5">
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

<script>
import { ref, reactive, onMounted, watch } from 'vue';
import { useAnalyticsApi } from '../../composables/useAnalyticsApi';
import { debounce } from 'lodash';
import GapChart from '../dashboard/GapChart.vue';
import ImpactSnapshot from '../dashboard/ImpactSnapshot.vue';
import IndicatorList from '../dashboard/IndicatorList.vue';
import TeamCulturePulse from '../dashboard/TeamCulturePulse.vue';
import TeamScatter from '../dashboard/TeamScatter.vue';
import TemperatureGauge from '../dashboard/TemperatureGauge.vue';

export default {
    name: 'AnalyticsDashboard',
    components: {
        GapChart,
        ImpactSnapshot,
        IndicatorList,
        TeamCulturePulse,
        TeamScatter,
        TemperatureGauge,
    },
    props: {
        user: {
            type: Object,
            required: true
        },
        initialCompanyId: {
            type: Number,
            required: true
        }
    },
    setup(props) {
        const { getDashboardData } = useAnalyticsApi();
        const isLoading = ref(true);
        const error = ref(null);
        const data = ref({});
        const options = reactive({
            departments: [],
            teamleads: [],
            waves: [],
            exist_departments: []
        });

        const filters = reactive({
            department: '',
            team: '',
            wave: ''
        });

        const fetchData = async () => {
            isLoading.value = true;
            error.value = null;
            try {
                const response = await getDashboardData({
                    company_id: props.initialCompanyId,
                    department: filters.department,
                    team: filters.team,
                    wave: filters.wave
                });

                data.value = response.data;
                
                // Update options only if they are empty (initial load)
                if (options.departments.length === 0) {
                    options.departments = response.filters.departments;
                    options.teamleads = response.filters.teamleads;
                    options.waves = response.filters.waves;
                    options.exist_departments = response.filters.exist_departments;
                }
            } catch (err) {
                console.error('Error fetching analytics:', err);
                error.value = 'Failed to load dashboard data. Please try again.';
            } finally {
                isLoading.value = false;
            }
        };

        const debouncedFetch = debounce(fetchData, 500);

        const resetFilters = () => {
            filters.department = '';
            filters.team = '';
            filters.wave = '';
            // debouncedFetch will be triggered by the watcher
        };

        // Helper functions for TeamCulturePulse
        const getPositiveItems = (items) => {
            if (!items) return [];
            return items.filter(i => i.polarity === 'positive').slice(0, 3);
        };

        const getNegativeItems = (items) => {
            if (!items) return [];
            return items.filter(i => i.polarity === 'negative').slice(0, 3);
        };

        // Watch for filter changes to re-fetch data
        watch(filters, () => {
            debouncedFetch();
        }, { deep: true });

        onMounted(() => {
            fetchData();
        });

        return {
            isLoading,
            error,
            data,
            filters,
            options,
            resetFilters,
            fetchData,
            getPositiveItems,
            getNegativeItems
        };
    }
}
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
