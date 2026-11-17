import { test as base } from '@shopware-ag/acceptance-test-suite';

export type ExecuteZettleWebhookTask = (
    salesChannelId: string,
    body: Record<string, string>,
    signature?: string,
) => () => Promise<number>;

export const ExecuteZettleWebhook = base.extend<FixtureTypes>({
    ExecuteZettleWebhook: async ({ request }, use) => {
        const task: ExecuteZettleWebhookTask = (salesChannelId, body, signature) => {
            return async function ExecuteZettleWebhook() {
                const response = await request.post(`api/_action/paypal/pos/webhook/execute/${salesChannelId}`, {
                    headers: signature ? { 'x-izettle-signature': signature } : undefined,
                    form: body,
                });

                return response.status();
            };
        };

        await use(task);
    },
});
