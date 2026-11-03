import { defineConfig } from '@playwright/test';
import { existsSync } from 'node:fs';

const isLive = Boolean(process.env.QA_TARGET_URL);
export default defineConfig({
  testDir: './tests/QA',
  fullyParallel: false,
  workers: 1,
  retries: 0,
  timeout: 45000,
  reporter: [['list'], ['json', {outputFile:'artifacts/qa/playwright-results.json'}]],
  use: {
    baseURL: isLive ? process.env.QA_TARGET_URL : 'http://127.0.0.1:8010',
    browserName: 'chromium',
    launchOptions: {
      executablePath: process.env.PLAYWRIGHT_CHROMIUM_EXECUTABLE_PATH ||
        (existsSync('/usr/bin/chromium') ? '/usr/bin/chromium' : undefined),
    },
    screenshot: 'only-on-failure',
    trace: 'retain-on-failure',
    actionTimeout: 12000,
  },
  webServer: isLive ? undefined : {
    command: 'bash scripts/browser-server.sh',
    url: 'http://127.0.0.1:8010/up',
    reuseExistingServer: false,
    timeout: 45000,
  },
});
