import { test as base } from '@shopware-ag/acceptance-test-suite';

export type PayPalWebhookCall = {
    token?: string
    body: Record<string, unknown>
};

export type ExecutePayPalWebhookTask = (call: PayPalWebhookCall) => () => Promise<{ status: number, body: string }>;

export const ExecutePayPalWebhook = base.extend<FixtureTypes>({
    ExecutePayPalWebhook: async ({ request }, use) => {
        const task: ExecutePayPalWebhookTask = ({ token, body }) => {
            return async function ExecutePayPalWebhook() {
                const path = token
                    ? `api/_action/paypal/webhook/execute?sw-token=${token}`
                    : 'api/_action/paypal/webhook/execute';
                const response = await request.post(path, {
                    data: body,
                });

                return {
                    status: response.status(),
                    body: await response.text(),
                };
            };
        };

        await use(task);
    },
});
