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
import AnalyticsDashboard from './components/analytics/AnalyticsDashboard.vue';

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
app.component('analytics-dashboard', AnalyticsDashboard);

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

// Legacy widget mounting code removed as part of Dashboard Analytics refactor
// The following components are now handled by AnalyticsDashboard.vue:
// - GapChart
// - IndicatorList
// - TeamScatter
// - TeamCulturePulse
// - ImpactSnapshot
// - TemperatureGauge

// Team Management Dashboard
const teamManagementRoot = document.getElementById('team-management-app');
if (teamManagementRoot) {
    const userRole = parseInt(teamManagementRoot.dataset.userRole);
    const teamApp = createApp(TeamManagementDashboard, { userRole });
    teamApp.component('toast-container', ToastContainer);
    teamApp.mount('#team-management-app');
}
