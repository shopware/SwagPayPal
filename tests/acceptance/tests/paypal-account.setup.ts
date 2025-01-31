import { test } from '@fixtures/AcceptanceTest';
import { expect } from '@shopware-ag/acceptance-test-suite';
import type * as PayPal from 'SwagPayPal/types';

test('PayPal Account setup', {}, async ({ AdminApiContext }) => {
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

    const isSandbox = Boolean(process.env.PAYPAL_SANDBOX);
    // eslint-disable-next-line playwright/no-conditional-in-test
    const prefix = isSandbox ? 'Sandbox' : '';

    const response = await AdminApiContext.post('./_action/paypal/save-settings', {
        data: {
            null: {
                [`SwagPayPal.settings.clientId${prefix}`]: String(process.env.PAYPAL_CLIENT_ID),
                [`SwagPayPal.settings.clientSecret${prefix}`]: String(process.env.PAYPAL_CLIENT_SECRET),
                [`SwagPayPal.settings.merchantPayerId${prefix}`]: String(process.env.PAYPAL_MERCHANT_ID),
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
