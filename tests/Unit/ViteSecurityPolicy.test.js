import { afterEach, describe, expect, it, vi } from 'vitest';
import config from '../../vite.config.js';

afterEach(() => vi.unstubAllEnvs());

describe('Vite development exposure', () => {
    it('defaults to loopback without accepting arbitrary Host headers', () => {
        vi.stubEnv('VITE_DEV_HOST', '');
        vi.stubEnv('VITE_DEV_ALLOWED_HOSTS', '');

        const { server } = config({ mode: 'development' });

        expect(server.host).toBe('127.0.0.1');
        expect(server.allowedHosts).toEqual([]);
    });

    it('allows a remote development environment to name its exact hosts', () => {
        vi.stubEnv('VITE_DEV_HOST', '0.0.0.0');
        vi.stubEnv('VITE_DEV_ALLOWED_HOSTS', ' preview.example.test, ,localhost ');

        const { server } = config({ mode: 'development' });

        expect(server.host).toBe('0.0.0.0');
        expect(server.allowedHosts).toEqual(['preview.example.test', 'localhost']);
    });
});
