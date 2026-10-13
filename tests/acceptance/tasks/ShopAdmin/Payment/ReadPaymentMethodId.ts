import { test as base } from '@shopware-ag/acceptance-test-suite';

const paymentMethodNames: Record<string, string> = {
    swag_paypal_paypal: 'PayPal',
};

export type ReadPaymentMethodIdTask = (technicalName: string) => () => Promise<string>;

export const ReadPaymentMethodId = base.extend<FixtureTypes>({
    ReadPaymentMethodId: async ({ TestDataService }, use) => {
        const task: ReadPaymentMethodIdTask = (technicalName) => {
            return async function ReadPaymentMethodId() {
                const method = await TestDataService.getPaymentMethod(paymentMethodNames[technicalName] ?? technicalName);
                if (!method?.id) {
                    throw new Error(`Payment method ${technicalName} was not found.`);
                }

                return method.id;
            };
        };

        await use(task);
    },
});
