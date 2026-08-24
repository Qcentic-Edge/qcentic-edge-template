import { readFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { describe, expect, it, vi } from 'vitest';

vi.hoisted(() => {
    vi.stubGlobal('window', globalThis);
});

vi.mock('laravel-echo', () => ({ default: vi.fn() }));
vi.mock('pusher-js', () => ({ default: vi.fn() }));

import Echo from 'laravel-echo';
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

    it('configures Echo wsHost from VITE_REVERB_HOST', () => {
        expect(Echo).toHaveBeenCalledWith(
            expect.objectContaining({
                broadcaster: 'reverb',
                wsHost: 'reverb.test',
            }),
        );
    });
});
