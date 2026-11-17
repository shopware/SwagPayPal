import { test as base, expect } from '@shopware-ag/acceptance-test-suite';

export type RestorePayPalContextTask = (token: string) => () => Promise<string>;

export const RestorePayPalContext = base.extend<FixtureTypes>({
    RestorePayPalContext: async ({ request, DefaultSalesChannel }, use) => {
        const task: RestorePayPalContextTask = (token) => {
            return async function RestorePayPalContext() {
                // PayPal sends an invalid token to checkout confirm. A visitor with no cart
                // is then sent on to the cart, so the request never stays on confirm or finish.
                // The test sales channel keeps the request on the same channel the token claims.
                const base = DefaultSalesChannel.url.endsWith('/')
                    ? DefaultSalesChannel.url
                    : `${DefaultSalesChannel.url}/`;
                const response = await request.get(`${base}paypal/restore-context/${token}`, { maxRedirects: 5 });

                expect(response.ok()).toBeTruthy();

                return response.url();
            };
        };

        await use(task);
    },
});
