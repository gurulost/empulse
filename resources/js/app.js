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

const surveyRoot = document.getElementById('survey-app');
if (surveyRoot) {
    const props = {
        definitionUrl: surveyRoot.dataset.definitionUrl,
        submitUrl: surveyRoot.dataset.submitUrl,
        autosaveUrl: surveyRoot.dataset.autosaveUrl,
    };

    createApp(SurveyApp, props).mount(surveyRoot);
}

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
