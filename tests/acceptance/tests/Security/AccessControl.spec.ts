import { test, expect } from '@fixtures/AcceptanceTest';
import { openViewerClient } from '@services/PayPalViewerClient';
import { readPayPalSetting } from '@services/PayPalSystemConfig';

function shopBaseUrl(): string {
    const baseURL = test.info().project.use.baseURL;
    if (!baseURL) {
        throw new Error('APP_URL is required so this request reaches the shop admin route.');
    }

    return baseURL;
}

test('SEC-01 A PayPal viewer cannot save settings', async ({
    TestDataService,
    request,
}) => {
    const viewer = await openViewerClient(TestDataService, request);
    const liveSecretBefore = await readPayPalSetting(TestDataService.AdminApiClient, 'SwagPayPal.settings.clientSecret');
    const sandboxSecretBefore = await readPayPalSetting(TestDataService.AdminApiClient, 'SwagPayPal.settings.clientSecretSandbox');

    try {
        const response = await viewer.post('api/_action/system-config/batch', {
            data: {
                null: {
                    'SwagPayPal.settings.clientSecret': 'viewer-must-not-write-this',
                    'SwagPayPal.settings.clientSecretSandbox': 'viewer-must-not-write-this',
                },
            },
        });

        expect(response.status()).toBe(403);
        expect(await readPayPalSetting(TestDataService.AdminApiClient, 'SwagPayPal.settings.clientSecret') === liveSecretBefore, 'Live client secret must stay unchanged').toBe(true);
        expect(await readPayPalSetting(TestDataService.AdminApiClient, 'SwagPayPal.settings.clientSecretSandbox') === sandboxSecretBefore, 'Sandbox client secret must stay unchanged').toBe(true);
    }
    finally {
        await viewer.dispose();
    }
});

test('SEC-10 A PayPal viewer cannot register or delete the webhook', async ({
    TestDataService,
    request,
}) => {
    const viewer = await openViewerClient(TestDataService, request);
    const webhookIdBefore = await readPayPalSetting(TestDataService.AdminApiClient, 'SwagPayPal.settings.webhookId');

    try {
        const registerResponse = await viewer.post('api/_action/paypal/webhook/register/null');
        const deregisterResponse = await viewer.delete('api/_action/paypal/webhook/deregister/null');

        expect(registerResponse.status()).toBe(403);
        expect(deregisterResponse.status()).toBe(403);
        expect(await readPayPalSetting(TestDataService.AdminApiClient, 'SwagPayPal.settings.webhookId') === webhookIdBefore, 'Webhook id must stay unchanged').toBe(true);
    }
    finally {
        await viewer.dispose();
    }
});

test('SEC-13 Storefront session cannot call PayPal admin routes', { tag: ['@Storefront'] }, async ({
    ShopCustomer,
    StorefrontAccountLogin,
    TestDataService,
    Login,
}) => {
    const customer = await TestDataService.createCustomer();

    await ShopCustomer.attemptsTo(Login(customer));

    const response = await StorefrontAccountLogin.page.request.get(
        new URL('api/_action/paypal/webhook/status/null', shopBaseUrl()).href,
    );
    const body = await response.text();

    expect([401, 403]).toContain(response.status());
    expect(body).not.toContain('webhookExecuteToken');
});
