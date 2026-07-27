import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { createApp, nextTick } from 'vue';

const mocks = vi.hoisted(() => ({
    getDashboardData: vi.fn(),
    ensureOnboardingSessionStarted: vi.fn(() => Promise.resolve()),
    trackOnboardingEvent: vi.fn(() => Promise.resolve()),
    trackOnboardingEventOnce: vi.fn(() => Promise.resolve()),
}));

vi.mock('../../resources/js/composables/useAnalyticsApi', () => ({
    useAnalyticsApi: () => ({ getDashboardData: mocks.getDashboardData }),
}));

vi.mock('../../resources/js/lib/onboardingTelemetry', () => ({
    ensureOnboardingSessionStarted: mocks.ensureOnboardingSessionStarted,
    trackOnboardingEvent: mocks.trackOnboardingEvent,
    trackOnboardingEventOnce: mocks.trackOnboardingEventOnce,
}));

vi.mock('../../resources/js/components/dashboard/GapChart.vue', () => ({ default: { template: '<div data-child-stub></div>' } }));
vi.mock('../../resources/js/components/dashboard/ImpactSnapshot.vue', () => ({ default: { template: '<div data-child-stub></div>' } }));
vi.mock('../../resources/js/components/dashboard/IndicatorList.vue', () => ({ default: { template: '<div data-child-stub></div>' } }));
vi.mock('../../resources/js/components/dashboard/TeamCulturePulse.vue', () => ({ default: { template: '<div data-child-stub></div>' } }));
vi.mock('../../resources/js/components/dashboard/TeamScatter.vue', () => ({ default: { template: '<div data-child-stub></div>' } }));
vi.mock('../../resources/js/components/dashboard/TemperatureGauge.vue', () => ({ default: { template: '<div data-child-stub></div>' } }));
vi.mock('../../resources/js/components/analytics/SetupChecklist.vue', () => ({
    default: { template: '<div data-testid="setup-checklist">Setup checklist</div>' },
}));

import AnalyticsDashboard from '../../resources/js/components/analytics/AnalyticsDashboard.vue';

const mounted = [];

const mountDashboard = () => {
    const root = document.createElement('div');
    document.body.appendChild(root);
    const app = createApp(AnalyticsDashboard, {
        user: {
            id: 7,
            company_id: 42,
            role: 1,
            is_admin: 0,
        },
        initialCompanyId: 42,
        companies: [],
    });
    app.mount(root);
    mounted.push({ app, root });

    return root;
};

const flushAsyncState = async () => {
    await Promise.resolve();
    await Promise.resolve();
    await nextTick();
};

beforeEach(() => {
    mocks.getDashboardData.mockReset();
    mocks.ensureOnboardingSessionStarted.mockClear();
    mocks.trackOnboardingEvent.mockClear();
    mocks.trackOnboardingEventOnce.mockClear();
});

afterEach(() => {
    while (mounted.length) {
        const { app, root } = mounted.pop();
        app.unmount();
        root.remove();
    }
    vi.restoreAllMocks();
});

describe('AnalyticsDashboard governed states', () => {
    it('announces a loading state while the API request is pending', async () => {
        mocks.getDashboardData.mockReturnValue(new Promise(() => {}));

        const root = mountDashboard();
        await nextTick();

        expect(root.querySelector('[role="status"]')).not.toBeNull();
        expect(root.textContent).toContain('Loading analytics data...');
    });

    it('renders the privacy-safe suppressed state without chart content', async () => {
        mocks.getDashboardData.mockResolvedValue({
            data: {
                availability: 'suppressed',
                sample: {
                    reason: 'Results are hidden below the minimum sample.',
                    valid_n: 4,
                    minimum_n: 5,
                    completion_rate: 0.8,
                },
            },
            setup: {},
            filters: {},
        });

        const root = mountDashboard();
        await flushAsyncState();

        expect(root.textContent).toContain('Results are protected');
        expect(root.textContent).toContain('Results are hidden below the minimum sample.');
        expect(root.textContent).toContain('4 valid responses');
        expect(root.textContent).toContain('5 required');
        expect(root.querySelector('.dashboard-content')).toBeNull();
    });

    it('renders a setup-aware empty state when eligible analytics have no measures yet', async () => {
        mocks.getDashboardData.mockResolvedValue({
            data: { availability: 'eligible' },
            setup: {
                recipient_count: 0,
                department_count: 0,
                has_live_survey: false,
                billing_allows_scheduling: false,
                response_count: 0,
            },
            filters: {},
        });

        const root = mountDashboard();
        await flushAsyncState();

        expect(root.querySelector('[data-testid="setup-checklist"]')).not.toBeNull();
        expect(root.textContent).toContain('Launch your first survey wave');
        expect(root.textContent).toContain('Add Team Members');
    });

    it('renders the API error and a retry control', async () => {
        vi.spyOn(console, 'error').mockImplementation(() => {});
        mocks.getDashboardData.mockRejectedValue({
            response: { data: { message: 'Analytics service is temporarily unavailable.' } },
        });

        const root = mountDashboard();
        await flushAsyncState();

        const alert = root.querySelector('[role="alert"], .alert-danger');
        expect(alert).not.toBeNull();
        expect(alert.textContent).toContain('Analytics service is temporarily unavailable.');
        expect(alert.textContent).toContain('Try Again');
    });
});
