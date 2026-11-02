import { test as base, expect } from '@shopware-ag/acceptance-test-suite';

export type ReadPaymentMethodIdTask = (
    value: string,
    field?: 'technicalName' | 'handlerIdentifier',
) => () => Promise<string>;

export const ReadPaymentMethodId = base.extend<FixtureTypes>({
    ReadPaymentMethodId: async ({ TestDataService }, use) => {
        const task: ReadPaymentMethodIdTask = (value, field = 'technicalName') => {
            return async function ReadPaymentMethodId() {
                const response = await TestDataService.AdminApiClient.post('search/payment-method', {
                    data: {
                        limit: 1,
                        filter: [
                            {
                                type: 'equals',
                                field,
                                value,
                            },
                        ],
                    },
                });

                expect(response.ok()).toBeTruthy();

                const { data } = await response.json() as { data: { id: string }[] };
                if (!data[0]?.id) {
                    throw new Error(`Payment method ${value} was not found.`);
                }

                return data[0].id;
            };
        };

        await use(task);
    },
});
