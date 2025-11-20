/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

import './bootstrap';
import { createApp } from 'vue';
import SurveyApp from './components/survey/SurveyApp.vue';
import GapChart from './components/dashboard/GapChart.vue';
import IndicatorList from './components/dashboard/IndicatorList.vue';
import TeamScatter from './components/dashboard/TeamScatter.vue';
import TeamCulturePulse from './components/dashboard/TeamCulturePulse.vue';
import ImpactSnapshot from './components/dashboard/ImpactSnapshot.vue';
import TemperatureGauge from './components/dashboard/TemperatureGauge.vue';
import AdminDashboard from './components/admin/AdminDashboard.vue';
import SurveyBuilder from './components/builder/SurveyBuilder.vue';
import ReportsDashboard from './components/reports/ReportsDashboard.vue';
import AppSidebar from './components/layout/AppSidebar.vue';
import ToastContainer from './components/common/ToastContainer.vue';
import TeamManagementDashboard from './components/team/TeamManagementDashboard.vue';

const app = createApp({});

app.component('survey-app', SurveyApp);
app.component('gap-chart', GapChart);
app.component('indicator-list', IndicatorList);
app.component('team-scatter', TeamScatter);
app.component('team-culture-pulse', TeamCulturePulse);
app.component('impact-snapshot', ImpactSnapshot);
app.component('temperature-gauge', TemperatureGauge);
app.component('admin-dashboard', AdminDashboard);
app.component('survey-builder', SurveyBuilder);
app.component('reports-dashboard', ReportsDashboard);
app.component('app-sidebar', AppSidebar);
app.component('toast-container', ToastContainer);
app.component('team-management-dashboard', TeamManagementDashboard);

// Only mount Vue app if there are components to mount (authenticated users)
// For guest users (landing page), we don't need Vue to mount and replace content
const appElement = document.getElementById('app');
if (appElement && (appElement.querySelector('[data-vue-component]') || document.querySelector('.main-content-wrapper'))) {
    app.mount('#app');
}
const surveyRoot = document.getElementById('survey-app');
if (surveyRoot) {
    const props = {
        definitionUrl: surveyRoot.dataset.definitionUrl,
        submitUrl: surveyRoot.dataset.submitUrl,
        autosaveUrl: surveyRoot.dataset.autosaveUrl,
    };

    // The original code mounted SurveyApp directly.
    // With global registration, it's assumed the component will be used in HTML.
    // If a specific mount point is still needed for SurveyApp with props,
    // the following line would be used, but it conflicts with the global app.
    // For now, we'll assume the global app handles it.
    // createApp(SurveyApp, props).mount(surveyRoot);
}

const reportsRoot = document.getElementById('reports-dashboard-root');
if (reportsRoot) {
    const reportsApp = createApp(ReportsDashboard);
    reportsApp.mount(reportsRoot);
}

// The following blocks for mounting individual components are now redundant
// if the intention is to use a single global app and rely on components
// being rendered directly in the HTML via their custom element tags.
// However, to maintain existing functionality for dynamic data updates,
// we will adapt these to use the globally registered components if they are
// still intended to be dynamically mounted/unmounted.
// For the purpose of this specific instruction, we'll keep the original
// mounting logic for dynamic updates, but the global registration is now present.

let gapApp = null;
const gapChartRoot = document.getElementById('gap-chart-root');
if (gapChartRoot) {
    const items = JSON.parse(gapChartRoot.dataset.items || '[]');
    gapApp = createApp(GapChart, { items });
    gapApp.mount(gapChartRoot);
}

let indicatorApp = null;
const indicatorRoot = document.getElementById('indicator-list-root');
if (indicatorRoot) {
    const indicators = JSON.parse(indicatorRoot.dataset.items || '[]');
    indicatorApp = createApp(IndicatorList, { indicators });
    indicatorApp.mount(indicatorRoot);
}

let teamScatterApp = null;
const teamScatterRoot = document.getElementById('team-scatter-root');
if (teamScatterRoot) {
    const points = JSON.parse(teamScatterRoot.dataset.items || '[]');
    teamScatterApp = createApp(TeamScatter, { points });
    teamScatterApp.mount(teamScatterRoot);
}

let teamCultureApp = null;
const teamCultureRoot = document.getElementById('team-culture-root');
if (teamCultureRoot) {
    const props = {
        score: Number(teamCultureRoot.dataset.score),
        positive: Number(teamCultureRoot.dataset.positive),
        negative: Number(teamCultureRoot.dataset.negative),
        positiveItems: JSON.parse(teamCultureRoot.dataset.positiveItems || '[]'),
        negativeItems: JSON.parse(teamCultureRoot.dataset.negativeItems || '[]'),
    };
    teamCultureApp = createApp(TeamCulturePulse, props);
    teamCultureApp.mount(teamCultureRoot);
}

let impactApp = null;
const impactRoot = document.getElementById('impact-root');
if (impactRoot) {
    const props = {
        positive: Number(impactRoot.dataset.positive),
        importance: Number(impactRoot.dataset.importance),
        desire: Number(impactRoot.dataset.desire),
    };
    impactApp = createApp(ImpactSnapshot, props);
    impactApp.mount(impactRoot);
}

let tempGaugeApp = null;
const tempGaugeRoot = document.getElementById('temp-gauge-root');
if (tempGaugeRoot) {
    tempGaugeApp = createApp(TemperatureGauge, { score: Number(tempGaugeRoot.dataset.score) });
    tempGaugeApp.mount(tempGaugeRoot);
}

const applyFiltersBtn = document.getElementById('apply-filters');
const resetFiltersBtn = document.getElementById('reset-filters');
const departmentSelect = document.getElementById('filter-department');
const teamSelect = document.getElementById('filter-team');
const waveSelect = document.getElementById('filter-wave');
const filterStatus = document.getElementById('filter-status');

async function refreshDashboard(params = {}) {
    const query = new URLSearchParams(params).toString();
    setFilterLoading(true, 'Loading...');
    const response = await fetch(`/dashboard/analytics?${query}`, {
        headers: { 'Accept': 'application/json' },
    });
    if (!response.ok) {
        setFilterLoading(false, 'Failed to load data.');
        return;
    }

    const payload = await response.json();
    const data = payload.data ?? {};

    const gapRoot = document.getElementById('gap-chart-root');
    if (gapChartRoot && data.gap_chart) {
        gapChartRoot.dataset.items = JSON.stringify(data.gap_chart);
        if (gapApp) {
            gapApp.unmount();
        }
        gapApp = createApp(GapChart, { items: data.gap_chart });
        gapApp.mount(gapChartRoot);
    }

    if (indicatorRoot && data.indicators) {
        indicatorRoot.dataset.items = JSON.stringify(data.indicators);
        if (indicatorApp) {
            indicatorApp.unmount();
        }
        indicatorApp = createApp(IndicatorList, { indicators: data.indicators });
        indicatorApp.mount(indicatorRoot);
    }

    if (teamScatterRoot && data.team_scatter) {
        teamScatterRoot.dataset.items = JSON.stringify(data.team_scatter);
        if (teamScatterApp) {
            teamScatterApp.unmount();
        }
        teamScatterApp = createApp(TeamScatter, { points: data.team_scatter });
        teamScatterApp.mount(teamScatterRoot);
    }

    if (teamCultureRoot && data.team_culture) {
        const props = {
            score: data.team_culture.score,
            positive: data.team_culture.positive,
            negative: data.team_culture.negative,
            positiveItems: data.team_culture.items.filter(i => i.polarity === 'positive').slice(0, 3),
            negativeItems: data.team_culture.items.filter(i => i.polarity === 'negative').slice(0, 3),
        };
        if (teamCultureApp) teamCultureApp.unmount();
        teamCultureApp = createApp(TeamCulturePulse, props);
        teamCultureApp.mount(teamCultureRoot);
    }

    if (impactRoot && data.impact_series) {
        const props = {
            positive: data.impact_series.positive,
            importance: data.impact_series.importance,
            desire: data.impact_series.desire,
        };
        if (impactApp) impactApp.unmount();
        impactApp = createApp(ImpactSnapshot, props);
        impactApp.mount(impactRoot);
    }

    if (tempGaugeRoot && data.weighted_indicator) {
        if (tempGaugeApp) tempGaugeApp.unmount();
        tempGaugeApp = createApp(TemperatureGauge, { score: data.weighted_indicator });
        tempGaugeApp.mount(tempGaugeRoot);
    }

    setFilterLoading(false);
}

function setFilterLoading(isLoading, message = '') {
    if (!filterStatus) {
        return;
    }

    if (isLoading) {
        filterStatus.textContent = message;
        filterStatus.classList.remove('d-none');
        applyFiltersBtn?.setAttribute('disabled', 'disabled');
        resetFiltersBtn?.setAttribute('disabled', 'disabled');
    } else {
        filterStatus.textContent = message;
        filterStatus.classList.toggle('d-none', !message);
        applyFiltersBtn?.removeAttribute('disabled');
        resetFiltersBtn?.removeAttribute('disabled');
    }
}

if (applyFiltersBtn) {
    applyFiltersBtn.addEventListener('click', () => {
        const params = {};
        if (departmentSelect?.value) {
            params.department = departmentSelect.value;
        }
        if (teamSelect?.value) {
            params.team = teamSelect.value;
        }
        if (waveSelect?.value) {
            params.wave = waveSelect.value;
        }
        refreshDashboard(params);
    });
}

if (resetFiltersBtn) {
    resetFiltersBtn.addEventListener('click', () => {
        if (departmentSelect) {
            departmentSelect.value = '';
        }
        if (teamSelect) {
            teamSelect.value = '';
        }
        if (waveSelect) {
            waveSelect.value = '';
        }
        refreshDashboard({});
    });
}

// Team Management Dashboard
const teamManagementRoot = document.getElementById('team-management-app');
if (teamManagementRoot) {
    const userRole = parseInt(teamManagementRoot.dataset.userRole);
    const teamApp = createApp(TeamManagementDashboard, { userRole });
    teamApp.component('toast-container', ToastContainer);
    teamApp.mount('#team-management-app');
}
