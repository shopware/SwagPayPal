import { request } from '@playwright/test';
import { test as base } from '@shopware-ag/acceptance-test-suite';

const SANDBOX_CARD_NUMBER = '4012888888881881';
const SANDBOX_CARD_SECURITY_CODE = '123';
const SANDBOX_CARD_EXPIRY = '2030-12';

export type CaptureSandboxPayPalOrderTask = () => () => Promise<string>;

export const CaptureSandboxPayPalOrder = base.extend<FixtureTypes>({
    CaptureSandboxPayPalOrder: async ({ PayPalDataProvider }, use) => {
        const task: CaptureSandboxPayPalOrderTask = () => {
            return async function CaptureSandboxPayPalOrder() {
                if (!PayPalDataProvider.get('SANDBOX', true)) {
                    throw new Error('Refusing to create a card capture against the live PayPal API.');
                }

                const clientId = PayPalDataProvider.get('CLIENT_ID');
                const clientSecret = PayPalDataProvider.get('CLIENT_SECRET');
                const authorization = Buffer.from(`${clientId}:${clientSecret}`).toString('base64');
                const paypal = await request.newContext({
                    baseURL: 'https://api-m.sandbox.paypal.com',
                    extraHTTPHeaders: {
                        Authorization: `Basic ${authorization}`,
                    },
                });

                try {
                    const tokenResponse = await paypal.post('/v1/oauth2/token', {
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        form: {
                            grant_type: 'client_credentials',
                        },
                    });

                    if (!tokenResponse.ok()) {
                        throw new Error(`PayPal sandbox auth failed with HTTP ${tokenResponse.status()}.`);
                    }

                    const tokenJson = await tokenResponse.json() as { access_token?: string };
                    if (!tokenJson.access_token) {
                        throw new Error('PayPal sandbox auth did not return an access token.');
                    }

                    const orderResponse = await paypal.post('/v2/checkout/orders', {
                        headers: {
                            Authorization: `Bearer ${tokenJson.access_token}`,
                            'Content-Type': 'application/json',
                            'PayPal-Request-Id': crypto.randomUUID(),
                            Prefer: 'return=representation',
                        },
                        data: {
                            intent: 'CAPTURE',
                            purchase_units: [
                                {
                                    amount: {
                                        currency_code: 'EUR',
                                        value: '50.00',
                                    },
                                },
                            ],
                            payment_source: {
                                card: {
                                    name: 'Test Buyer',
                                    number: SANDBOX_CARD_NUMBER,
                                    security_code: SANDBOX_CARD_SECURITY_CODE,
                                    expiry: SANDBOX_CARD_EXPIRY,
                                },
                            },
                        },
                    });

                    const orderText = await orderResponse.text();
                    if (!orderResponse.ok()) {
                        throw new Error(`PayPal sandbox order failed with HTTP ${orderResponse.status()}: ${orderText.slice(0, 500)}`);
                    }

                    const created = JSON.parse(orderText) as { id?: string, status?: string };
                    if (!created.id) {
                        throw new Error('PayPal sandbox order did not return an id.');
                    }

                    if (created.status === 'COMPLETED') {
                        return created.id;
                    }

                    const captureResponse = await paypal.post(`/v2/checkout/orders/${created.id}/capture`, {
                        headers: {
                            Authorization: `Bearer ${tokenJson.access_token}`,
                            'Content-Type': 'application/json',
                            'PayPal-Request-Id': crypto.randomUUID(),
                            Prefer: 'return=representation',
                        },
                    });
                    const captureText = await captureResponse.text();
                    if (!captureResponse.ok()) {
                        throw new Error(`PayPal sandbox capture failed with HTTP ${captureResponse.status()}: ${captureText.slice(0, 500)}`);
                    }

                    const captured = JSON.parse(captureText) as { id?: string, status?: string };
                    if (captured.status !== 'COMPLETED' || !captured.id) {
                        throw new Error(`PayPal sandbox order is ${captured.status ?? 'missing'}, not a completed capture.`);
                    }

                    return captured.id;
                }
                finally {
                    await paypal.dispose();
                }
            };
        };

        await use(task);
    },
});
