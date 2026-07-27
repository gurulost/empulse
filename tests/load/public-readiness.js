import http from 'k6/http';
import { check } from 'k6';

export const options = {
    scenarios: {
        steady_web_readiness: {
            executor: 'ramping-vus',
            startVUs: 0,
            stages: [
                { duration: '30s', target: 20 },
                { duration: '2m', target: 20 },
                { duration: '30s', target: 0 },
            ],
        },
    },
    thresholds: {
        http_req_failed: ['rate<0.001'],
        http_req_duration: ['p(95)<750', 'p(99)<1500'],
        checks: ['rate>0.999'],
    },
};

const baseUrl = __ENV.EMPULSE_BASE_URL;

export default function () {
    const live = http.get(`${baseUrl}/api/healthz`);
    check(live, {
        'liveness 200': (response) => response.status === 200,
        'liveness contract': (response) => response.json('status') === 'live',
    });

    const ready = http.get(`${baseUrl}/api/readyz`);
    check(ready, {
        'readiness 200': (response) => response.status === 200,
        'readiness contract': (response) => response.json('status') === 'ready',
    });

    const login = http.get(`${baseUrl}/login`);
    check(login, {
        'login 200': (response) => response.status === 200,
        'login has CSRF form': (response) => response.body.includes('_token'),
    });
}
