import { readFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { describe, expect, it } from 'vitest';

import './bootstrap.js';

const jsDir = dirname(fileURLToPath(import.meta.url));

function readJsSource(filename: string): string {
    return readFileSync(join(jsDir, filename), 'utf8');
}

describe('resources/js bootstrap', () => {
    it('does not reference Pusher Cloud hosts', () => {
        const source = ['app.js', 'bootstrap.js'].map(readJsSource).join('\n');

        expect(source).not.toMatch(/pusher\.com/i);
        expect(source).not.toMatch(/ws-[a-z0-9-]+\.pusher\.com/i);
    });
});
