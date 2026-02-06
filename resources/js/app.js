import './bootstrap';
import { createApp } from 'vue';

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

const mountByTagName = async (tagName, loader, propsFromElement = () => ({})) => {
    const elements = [...document.querySelectorAll(tagName)];
    if (!elements.length) {
        return;
    }

    const { default: Component } = await loader();

    elements.forEach((element) => {
        const props = propsFromElement(element);
        if (props === null) return;
        createApp(Component, props).mount(element);
    });
};

const mountById = async (id, loader, propsFromElement = () => ({})) => {
    const element = document.getElementById(id);
    if (!element) {
        return;
    }

    const { default: Component } = await loader();
    const props = propsFromElement(element);
    if (props === null) return;

    createApp(Component, props).mount(element);
};

const boot = async () => {
    const tasks = [
        () => mountByTagName('app-sidebar', () => import('./components/layout/AppSidebar.vue'), (element) =>
            getPropsFromAttributes(element, {
                user: ':user',
                currentRoute: 'current-route',
            }),
        ),
        () => mountByTagName('toast-container', () => import('./components/common/ToastContainer.vue')),
        () => mountByTagName('analytics-dashboard', () => import('./components/analytics/AnalyticsDashboard.vue'), (element) =>
            getPropsFromAttributes(element, {
                user: ':user',
                initialCompanyId: ':initial-company-id',
                companies: ':companies',
            }),
        ),
        () => mountByTagName('admin-dashboard', () => import('./components/admin/AdminDashboard.vue'), (element) =>
            getPropsFromAttributes(element, {
                user: ':user',
            }),
        ),
        () => mountById('survey-builder-root', () => import('./components/builder/SurveyBuilder.vue'), (element) => {
            const initialVersionId = parseInt(element.dataset.initialVersionId ?? '', 10);
            const surveyId = parseInt(element.dataset.surveyId ?? '', 10);

            if (!Number.isFinite(initialVersionId) || !Number.isFinite(surveyId)) {
                return null;
            }

            return { initialVersionId, surveyId };
        }),
        () => mountById('survey-app', () => import('./components/survey/SurveyApp.vue'), (element) => {
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
        }),
        () => mountById('reports-dashboard-root', () => import('./components/reports/ReportsDashboard.vue'), (element) =>
            getPropsFromAttributes(element, {
                user: 'data-user',
                initialCompanyId: 'data-initial-company-id',
                companies: 'data-companies',
            }),
        ),
        () => mountById('team-management-app', () => import('./components/team/TeamManagementDashboard.vue'), (element) => {
            const userRole = parseInt(element.dataset.userRole ?? '', 10);
            if (!Number.isFinite(userRole)) {
                return null;
            }

            return { userRole };
        }),
    ];

    for (const task of tasks) {
        try {
            await task();
        } catch (error) {
            console.error('Component mount failed:', error);
        }
    }
};

boot().catch((error) => {
    console.error('App bootstrap failed:', error);
});
