import { test, expect } from '@fixtures/AcceptanceTest';
import { readPayPalSetting, savePayPalSettings } from '@services/PayPalSystemConfig';

test('SEC-02 The storefront HTML does not contain the client secret', { tag: ['@Storefront'] }, async ({
    ShopCustomer,
    StorefrontProductDetail,
    TestDataService,
    PayPalDataProvider,
}) => {
    const clientSecret = PayPalDataProvider.get('CLIENT_SECRET');
    expect(clientSecret.length, 'PAYPAL_CLIENT_SECRET is required for this check').toBeGreaterThan(8);

    const product = await TestDataService.createBasicProduct();
    await ShopCustomer.goesTo(StorefrontProductDetail.url(product));

    const html = await StorefrontProductDetail.page.content();
    expect(html).not.toContain(clientSecret);
});

test('SEC-11 Live mode does not send the sandbox client id', { tag: ['@Storefront'] }, async ({
    ShopCustomer,
    StorefrontProductDetail,
    StorefrontCheckoutCart,
    AdminApiContext,
    TestDataService,
    AddProductToCart,
}) => {
    const liveKey = 'SwagPayPal.settings.clientId';
    const liveSecretKey = 'SwagPayPal.settings.clientSecret';
    const sandboxKey = 'SwagPayPal.settings.clientIdSandbox';
    const sandboxFlag = 'SwagPayPal.settings.sandbox';
    const cartExpress = 'SwagPayPal.settings.ecsCartEnabled';
    const previousLive = await readPayPalSetting(AdminApiContext, liveKey);
    const previousLiveSecret = await readPayPalSetting(AdminApiContext, liveSecretKey);
    const previousSandbox = await readPayPalSetting(AdminApiContext, sandboxKey);
    const previousFlag = await readPayPalSetting(AdminApiContext, sandboxFlag);
    const previousCartExpress = await readPayPalSetting(AdminApiContext, cartExpress);
    const liveMarker = 'live-client-id-sec-11';
    const sandboxMarker = 'sandbox-client-id-sec-11';
    const liveSecretMarker = 'live-client-secret-sec-11';
    const product = await TestDataService.createBasicProduct();

    try {
        await savePayPalSettings(AdminApiContext, {
            [liveKey]: liveMarker,
            [liveSecretKey]: liveSecretMarker,
            [sandboxKey]: sandboxMarker,
            [sandboxFlag]: false,
            [cartExpress]: true,
        });
        await TestDataService.clearCaches();

        await ShopCustomer.goesTo(StorefrontProductDetail.url(product));
        await ShopCustomer.attemptsTo(AddProductToCart(product));
        await ShopCustomer.goesTo(StorefrontCheckoutCart.url());
        const html = await StorefrontCheckoutCart.page.content();

        expect(html).toContain(liveMarker);
        expect(html).not.toContain(sandboxMarker);
        expect(html).not.toContain(liveSecretMarker);
    }
    finally {
        await savePayPalSettings(AdminApiContext, {
            [liveKey]: previousLive ?? null,
            [liveSecretKey]: previousLiveSecret ?? null,
            [sandboxKey]: previousSandbox ?? null,
            [sandboxFlag]: previousFlag ?? true,
            [cartExpress]: previousCartExpress ?? true,
        });
        await TestDataService.clearCaches();
    }
});

test('SEC-12 PUI customer-service instructions are not executed on the storefront', { tag: ['@Storefront'] }, async ({
    ShopCustomer,
    StorefrontProductDetail,
    AdminApiContext,
    TestDataService,
}) => {
    const key = 'SwagPayPal.settings.puiCustomerServiceInstructions';
    const previous = await readPayPalSetting(AdminApiContext, key);
    const payload = '<script>window.__xss=1</script>';
    const product = await TestDataService.createBasicProduct();

    try {
        await savePayPalSettings(AdminApiContext, {
            [key]: payload,
        });

        expect(await readPayPalSetting(AdminApiContext, key)).toBe(payload);

        // The setting is stored for PayPal and customer mail. The storefront page must not run it if a template echoes it.
        await ShopCustomer.goesTo(StorefrontProductDetail.url(product));
        const html = await StorefrontProductDetail.page.content();
        const executed: unknown = await StorefrontProductDetail.page.evaluate(() => {
            return (globalThis as { __xss?: unknown }).__xss;
        });

        expect(html).not.toContain(payload);
        expect(executed).toBeUndefined();
    }
    finally {
        await savePayPalSettings(AdminApiContext, {
            [key]: previous ?? 'Details zum Kundenservice finden Sie auf unserer Webseite',
        });
    }
});
