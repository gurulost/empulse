import { createHash } from 'node:crypto';
import { execFileSync } from 'node:child_process';
import { existsSync, mkdirSync, readdirSync, unlinkSync, writeFileSync } from 'node:fs';
import { isAbsolute, join, resolve } from 'node:path';
import { supportsNode } from './check-node-runtime.mjs';

// A project-specific prefix only: never update the machine's global npm.
const destination = process.argv[2];
if (!destination || !isAbsolute(destination) || resolve(destination) === '/') {
    throw new Error('An explicit absolute build-tool prefix is required.');
}
if (existsSync(destination) && readdirSync(destination).length !== 0) {
    throw new Error('Refusing to modify a nonempty build-tool prefix.');
}
if (!supportsNode(process.version)) {
    throw new Error('Use a supported patched Node runtime before bootstrapping npm.');
}

const version = '11.21.1';
const integrity = 'YTzL6QBEJXyHcTu0JiUc7RKMp+mzlJtl7rGLmERaXUQeFHIMc9atdr6G9M9C8vDWbv40epoTQAzNL2/PtZVDhQ==';
const response = await fetch(`https://registry.npmjs.org/npm/-/npm-${version}.tgz`);
if (!response.ok) throw new Error(`npm artifact request failed (${response.status}).`);
const artifact = Buffer.from(await response.arrayBuffer());
if (createHash('sha512').update(artifact).digest('base64') !== integrity) {
    throw new Error('npm artifact integrity mismatch; installation refused.');
}

mkdirSync(destination, { recursive: true });
const archive = join(destination, `npm-${version}.tgz`);
const userConfig = join(destination, 'bootstrap-user.npmrc');
const globalConfig = join(destination, 'bootstrap-global.npmrc');
writeFileSync(archive, artifact, { flag: 'wx', mode: 0o600 });
writeFileSync(userConfig, '', { flag: 'wx', mode: 0o600 });
writeFileSync(globalConfig, '', { flag: 'wx', mode: 0o600 });
try {
    execFileSync('npm', [
        'install', '--global', '--prefix', destination, '--ignore-scripts',
        '--no-audit', '--no-fund', archive,
    ], {
        cwd: destination,
        stdio: 'inherit',
        env: { ...process.env, npm_config_userconfig: userConfig, npm_config_globalconfig: globalConfig },
    });
} finally {
    unlinkSync(archive);
    unlinkSync(userConfig);
    unlinkSync(globalConfig);
}
process.stdout.write(`Verified build npm ${version} installed under the explicit prefix.\n`);
