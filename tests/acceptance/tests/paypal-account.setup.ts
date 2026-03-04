import { test } from '@fixtures/AcceptanceTest';
import { expect } from '@shopware-ag/acceptance-test-suite';
import type * as PayPal from 'SwagPayPal/types';

test('PayPal Account setup', {}, async ({ 
    AdminApiContext, 
    PayPalDataProvider,
    TestDataService,
    }) => {
    const resetResponse = await AdminApiContext.post('./_action/paypal/save-settings', {
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
    const response = await TestDataService.setPayPalSettings('null',{
        [`SwagPayPal.settings.clientId${prefix}`]: PayPalDataProvider.get('CLIENT_ID'),
        [`SwagPayPal.settings.clientSecret${prefix}`]: PayPalDataProvider.get('CLIENT_SECRET'),
        [`SwagPayPal.settings.merchantPayerId${prefix}`]: PayPalDataProvider.get('MERCHANT_ID'),
        'SwagPayPal.settings.sandbox': isSandbox
    });

    const json = await response.json() as PayPal.Api.Operations<'saveSettings'>;
    expect(json).toHaveProperty('null');

    const payPalPaymentMethod = await TestDataService.getPaymentMethod('PayPal');
    TestDataService.setCleanUp(false);
    await TestDataService.assignSalesChannelPaymentMethod(TestDataService.defaultSalesChannel.id, payPalPaymentMethod.id);
});
