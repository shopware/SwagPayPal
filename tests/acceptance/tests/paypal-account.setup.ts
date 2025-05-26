import { test } from '@fixtures/AcceptanceTest';
import { expect } from '@shopware-ag/acceptance-test-suite';
import type * as PayPal from 'SwagPayPal/types';

test('PayPal Account setup', {}, async ({ AdminApiContext, PayPalDataProvider }) => {
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

    const isSandbox = PayPalDataProvider.get('sandbox');
    // eslint-disable-next-line playwright/no-conditional-in-test
    const prefix = isSandbox ? 'Sandbox' : '';

    const response = await AdminApiContext.post('./_action/paypal/save-settings', {
        data: {
            null: {
                [`SwagPayPal.settings.clientId${prefix}`]: PayPalDataProvider.get('client-id'),
                [`SwagPayPal.settings.clientSecret${prefix}`]: PayPalDataProvider.get('client-secret'),
                [`SwagPayPal.settings.merchantPayerId${prefix}`]: PayPalDataProvider.get('merchant-id'),
                'SwagPayPal.settings.sandbox': isSandbox,
            },
        },
    });

    expect(response.ok()).toBeTruthy();

    const json = await response.json() as PayPal.Api.Operations<'saveSettings'>;

    expect(json).toHaveProperty('null');
    expect(json.null[`${isSandbox ? 'sandbox' : 'live'}CredentialsChanged`]).toBeTruthy();
    expect(json.null[`${isSandbox ? 'sandbox' : 'live'}CredentialsValid`]).toBeTruthy();
});
