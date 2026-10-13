import { test as base } from '@shopware-ag/acceptance-test-suite';
import { readPayPalSetting, savePayPalSettings } from '@services/PayPalSystemConfig';

const WEBHOOK_TOKEN_KEY = 'SwagPayPal.settings.webhookExecuteToken';

export type ReplacedWebhookToken = {
    value: string
    restore: () => Promise<void>
};

export type ReplaceWebhookTokenTask = () => () => Promise<ReplacedWebhookToken>;

export const ReplaceWebhookToken = base.extend<FixtureTypes>({
    ReplaceWebhookToken: async ({ AdminApiContext, IdProvider }, use) => {
        const task: ReplaceWebhookTokenTask = () => {
            return async function ReplaceWebhookToken() {
                const previous = await readPayPalSetting(AdminApiContext, WEBHOOK_TOKEN_KEY);
                const value = IdProvider.getIdPair().uuid;

                await savePayPalSettings(AdminApiContext, {
                    [WEBHOOK_TOKEN_KEY]: value,
                });

                return {
                    value,
                    restore: async () => {
                        await savePayPalSettings(AdminApiContext, {
                            [WEBHOOK_TOKEN_KEY]: previous ?? null,
                        });
                    },
                };
            };
        };

        await use(task);
    },
});
