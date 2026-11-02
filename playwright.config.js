import { defineConfig } from '@playwright/test';
import { existsSync } from 'node:fs';

export default defineConfig({
    testDir: './tests/Browser',
    fullyParallel: false,
    workers: 1,
    timeout: 30000,
    use: {
        baseURL: 'http://127.0.0.1:8010',
        browserName: 'chromium',
        launchOptions: {
            executablePath: process.env.PLAYWRIGHT_CHROMIUM_EXECUTABLE_PATH || (existsSync('/usr/bin/chromium') ? '/usr/bin/chromium' : undefined),
        },
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
    },
    webServer: {
        command: 'bash scripts/browser-server.sh',
        url: 'http://127.0.0.1:8010/up',
        reuseExistingServer: false,
        timeout: 30000,
    },
});
