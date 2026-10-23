import { describe, expect, it } from 'vitest';
import { supportsNode } from '../../scripts/check-node-runtime.mjs';

describe('Node security runtime floors', () => {
    it('rejects vulnerable, EOL, prerelease and unqualified branches', () => {
        for (const version of ['22.23.3', '24.22.0', '20.20.0', '26.12.1', '22.23.4-rc.1', 'invalid']) {
            expect(supportsNode(version), version).toBe(false);
        }
    });

    it('accepts patched supported branches including subsequent patches', () => {
        for (const version of ['v22.23.4', '22.23.5', '22.24.0', '24.22.1', '24.23.0']) {
            expect(supportsNode(version), version).toBe(true);
        }
    });
});
