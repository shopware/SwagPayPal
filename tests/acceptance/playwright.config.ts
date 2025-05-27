import { defineConfig, devices } from '@playwright/test';
import dotenv from 'dotenv';
import * as process from 'node:process';

// Read from default ".env" file.
dotenv.config();

process.env['SHOPWARE_ADMIN_USERNAME'] = process.env['SHOPWARE_ADMIN_USERNAME'] || 'admin';
process.env['SHOPWARE_ADMIN_PASSWORD'] = process.env['SHOPWARE_ADMIN_PASSWORD'] || 'shopware';
process.env['MAILPIT_BASE_URL'] = process.env['MAILPIT_BASE_URL'] || process.env['MAILER_DSN'] || 'http://localhost:8025';

process.env['APP_URL'] = process.env['APP_URL'] ?? 'http://localhost:8000';

// make sure APP_URL ends with a slash
process.env['APP_URL'] = process.env['APP_URL'].replace(/\/+$/, '') + '/';
if (process.env['ADMIN_URL']) {
    process.env['ADMIN_URL'] = process.env['ADMIN_URL'].replace(/\/+$/, '') + '/';
} else {
    process.env['ADMIN_URL'] = process.env['APP_URL'] + 'admin/';
}

export default defineConfig({
    testDir: './tests',
    fullyParallel: true,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 2 : 0,
    workers: process.env.CI ? 1 : 1,
    reporter: 'html',
    timeout: 60_000,
    expect: {
        timeout: 15_000,
    },

    use: {
        /* Base URL to use in actions like `await page.goto('/')`. */
        baseURL: process.env['APP_URL'],
        trace: 'retain-on-failure',
        video: 'off',
    },

    // We abuse this to wait for the external webserver
    webServer: {
        command: 'sleep 1d',
        url: process.env['APP_URL'],
        reuseExistingServer: true,
    },

    projects: [
        {
            name: 'PayPal Setup',
            testMatch: /.*\.setup\.ts/,
            use: { trace: 'off' },
        },
        {
            name: 'PayPal',
            testMatch: /.*\.spec\.ts/,
            use: {
                ...devices['Desktop Chrome'],
                channel: 'chromium',
            },
            dependencies: ['PayPal Setup'],
        },
    ],
});
