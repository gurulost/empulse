import { pathToFileURL } from 'node:url';

export function supportsNode(version) {
    const match = /^v?(22|24)\.(\d+)\.(\d+)$/.exec(version);
    if (!match) return false;

    const [, major, minor, patch] = match.map(Number);
    const minimum = major === 22 ? [23, 4] : [22, 1];
    return minor > minimum[0] || (minor === minimum[0] && patch >= minimum[1]);
}

if (process.argv[1] && import.meta.url === pathToFileURL(process.argv[1]).href) {
    if (!supportsNode(process.version)) {
        process.stderr.write('Unsupported Node runtime. Require patched Node 22.23.4+ or 24.22.1+ on those branches.\n');
        process.exitCode = 1;
    }
}
