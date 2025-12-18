/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

import './bootstrap';
import { createApp } from 'vue';
import SurveyApp from './components/survey/SurveyApp.vue';
import AdminDashboard from './components/admin/AdminDashboard.vue';
import SurveyBuilder from './components/builder/SurveyBuilder.vue';
import ReportsDashboard from './components/reports/ReportsDashboard.vue';
import AppSidebar from './components/layout/AppSidebar.vue';
import ToastContainer from './components/common/ToastContainer.vue';
import TeamManagementDashboard from './components/team/TeamManagementDashboard.vue';
import AnalyticsDashboard from './components/analytics/AnalyticsDashboard.vue';

const parseProp = (raw) => {
    if (raw === null || raw === undefined) return undefined;

    const value = String(raw).trim();
    if (!value) return undefined;

    try {
        return JSON.parse(value);
    } catch {
        return value;
    }
};

const getPropsFromAttributes = (element, mapping) => {
    const props = {};

    for (const [propName, attributeName] of Object.entries(mapping)) {
        const raw = element.getAttribute(attributeName);
        const parsed = parseProp(raw);
        if (parsed !== undefined) {
            props[propName] = parsed;
        }
    }

    return props;
};

const mountByTagName = (tagName, Component, propsFromElement = () => ({})) => {
    document.querySelectorAll(tagName).forEach((element) => {
        const props = propsFromElement(element);
        if (props === null) return;
        createApp(Component, props).mount(element);
    });
};

const mountById = (id, Component, propsFromElement = () => ({})) => {
    const element = document.getElementById(id);
    if (!element) return;

    const props = propsFromElement(element);
    if (props === null) return;
    createApp(Component, props).mount(element);
};

// Layout (authenticated pages)
mountByTagName('app-sidebar', AppSidebar, (element) =>
    getPropsFromAttributes(element, {
        user: ':user',
        currentRoute: 'current-route',
    }),
);

mountByTagName('toast-container', ToastContainer);

// Analytics dashboard
mountByTagName('analytics-dashboard', AnalyticsDashboard, (element) =>
    getPropsFromAttributes(element, {
        user: ':user',
        initialCompanyId: ':initial-company-id',
    }),
);

// Workfit admin dashboard (modern layout)
mountByTagName('admin-dashboard', AdminDashboard, (element) =>
    getPropsFromAttributes(element, {
        user: ':user',
    }),
);

// Survey builder
mountById('survey-builder-root', SurveyBuilder, (element) => {
    const initialVersionId = parseInt(element.dataset.initialVersionId ?? '', 10);
    const surveyId = parseInt(element.dataset.surveyId ?? '', 10);

    if (!Number.isFinite(initialVersionId) || !Number.isFinite(surveyId)) {
        return null;
    }

    return { initialVersionId, surveyId };
});

// Survey taking flow
mountById('survey-app', SurveyApp, (element) => {
    const definitionUrl = element.dataset.definitionUrl;
    const submitUrl = element.dataset.submitUrl;
    const autosaveUrl = element.dataset.autosaveUrl;

    if (!definitionUrl || !submitUrl) {
        return null;
    }

    return {
        definitionUrl,
        submitUrl,
        autosaveUrl,
    };
});

// Reports
mountById('reports-dashboard-root', ReportsDashboard);

// Team management dashboard
mountById('team-management-app', TeamManagementDashboard, (element) => {
    const userRole = parseInt(element.dataset.userRole ?? '', 10);
    if (!Number.isFinite(userRole)) {
        return null;
    }

    return { userRole };
});
