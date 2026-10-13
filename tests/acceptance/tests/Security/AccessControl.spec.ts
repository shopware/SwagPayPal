import { test, expect } from '@fixtures/AcceptanceTest';
import { openViewerClient } from '@services/PayPalViewerClient';
import { readPayPalSetting } from '@services/PayPalSystemConfig';

test('SEC-01 A PayPal viewer cannot save settings', async ({
    AdminApiContext,
    TestDataService,
    request,
}) => {
    const viewer = await openViewerClient(TestDataService, request);
    const liveSecretBefore = await readPayPalSetting(AdminApiContext, 'SwagPayPal.settings.clientSecret');
    const sandboxSecretBefore = await readPayPalSetting(AdminApiContext, 'SwagPayPal.settings.clientSecretSandbox');

    try {
        const response = await viewer.post('api/_action/paypal/save-settings', {
            data: {
                null: {
                    'SwagPayPal.settings.clientSecret': 'viewer-must-not-write-this',
                    'SwagPayPal.settings.clientSecretSandbox': 'viewer-must-not-write-this',
                },
            },
        });

        expect(response.status()).toBe(403);
        expect(await readPayPalSetting(AdminApiContext, 'SwagPayPal.settings.clientSecret') === liveSecretBefore, 'Live client secret must stay unchanged').toBe(true);
        expect(await readPayPalSetting(AdminApiContext, 'SwagPayPal.settings.clientSecretSandbox') === sandboxSecretBefore, 'Sandbox client secret must stay unchanged').toBe(true);
    }
    finally {
        await viewer.dispose();
    }
});

test('SEC-10 A PayPal viewer cannot register or delete the webhook', async ({
    AdminApiContext,
    TestDataService,
    request,
}) => {
    const viewer = await openViewerClient(TestDataService, request);
    const webhookIdBefore = await readPayPalSetting(AdminApiContext, 'SwagPayPal.settings.webhookId');

    try {
        const registerResponse = await viewer.post('api/_action/paypal/webhook/register/null');
        const deregisterResponse = await viewer.delete('api/_action/paypal/webhook/deregister/null');

        expect(registerResponse.status()).toBe(403);
        expect(deregisterResponse.status()).toBe(403);
        expect(await readPayPalSetting(AdminApiContext, 'SwagPayPal.settings.webhookId') === webhookIdBefore, 'Webhook id must stay unchanged').toBe(true);
    }
    finally {
        await viewer.dispose();
    }
});

test('SEC-08 Agent route rejects a missing or bad bearer token', async ({
    request,
}) => {
    const missing = await request.post('api/paypal/v1/merchant-cart', {
        data: {},
    });
    const badToken = await request.post('api/paypal/v1/merchant-cart', {
        headers: {
            Authorization: 'Bearer not.a.jwt',
        },
        data: {},
    });

    expect(missing.status()).toBe(401);
    expect(badToken.status()).toBe(401);
});

test('SEC-13 Storefront session cannot call PayPal admin routes', { tag: ['@Storefront'] }, async ({
    ShopCustomer,
    StorefrontAccountLogin,
    TestDataService,
    Login,
}) => {
    const customer = await TestDataService.createCustomer();

    await ShopCustomer.attemptsTo(Login(customer));

    const shop = new URL(StorefrontAccountLogin.page.url());
    const response = await StorefrontAccountLogin.page.request.get(`${shop.origin}/api/_action/paypal/webhook/status/null`);
    const body = await response.text();

    expect([401, 403]).toContain(response.status());
    expect(body).not.toContain('webhookExecuteToken');
});
