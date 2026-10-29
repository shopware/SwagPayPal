import { test } from '@fixtures/AcceptanceTest';
import { expect } from '@shopware-ag/acceptance-test-suite';

test('PayPal Account setup', {}, async ({
    AdminApiContext,
    PayPalDataProvider,
    TestDataService,
}) => {
    const resetResponse = await AdminApiContext.post('./_action/system-config/batch', {
        data: {
            null: {
                'SwagPayPal.settings.clientId': null,
                'SwagPayPal.settings.clientSecret': null,
                'SwagPayPal.settings.merchantPayerId': null,
                'SwagPayPal.settings.clientIdSandbox': null,
                'SwagPayPal.settings.clientSecretSandbox': null,
                'SwagPayPal.settings.merchantPayerIdSandbox': null,
                'SwagPayPal.settings.sandbox': false,
            },
        },
    });

    expect(resetResponse.ok()).toBeTruthy();

    const isSandbox = PayPalDataProvider.get('SANDBOX');
    // eslint-disable-next-line playwright/no-conditional-in-test
    const prefix = isSandbox ? 'Sandbox' : '';
    await TestDataService.setPayPalSettings('null', {
        [`SwagPayPal.settings.clientId${prefix}`]: PayPalDataProvider.get('CLIENT_ID'),
        [`SwagPayPal.settings.clientSecret${prefix}`]: PayPalDataProvider.get('CLIENT_SECRET'),
        [`SwagPayPal.settings.merchantPayerId${prefix}`]: PayPalDataProvider.get('MERCHANT_ID'),
        'SwagPayPal.settings.sandbox': isSandbox,
        'SwagPayPal.settings.isLocalEnvironment': true,
    });

    const payPalPaymentMethod = await TestDataService.getPaymentMethodByDistinguishableName('PayPal | PayPal', false);
    TestDataService.setCleanUp(false);
    await TestDataService.assignSalesChannelPaymentMethod(TestDataService.defaultSalesChannel.id, payPalPaymentMethod.id);
});
