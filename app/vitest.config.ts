import { defineConfig } from 'vitest/config';

export default defineConfig({
    test: {
        include: ['resources/js/**/*.test.ts'],
        environment: 'node',
        env: {
            VITE_REVERB_APP_KEY: 'test-key',
            VITE_REVERB_HOST: 'reverb.test',
            VITE_REVERB_PORT: '8080',
            VITE_REVERB_SCHEME: 'http',
        },
    },
});

