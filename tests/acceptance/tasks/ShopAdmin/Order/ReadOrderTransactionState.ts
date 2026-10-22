import { test as base, expect } from '@shopware-ag/acceptance-test-suite';

export type OrderTransactionState = {
    id: string
    stateId: string
};

export type ReadOrderTransactionStateTask = (orderId: string) => () => Promise<OrderTransactionState>;

export const ReadOrderTransactionState = base.extend<FixtureTypes>({
    ReadOrderTransactionState: async ({ AdminApiContext }, use) => {
        const task: ReadOrderTransactionStateTask = (orderId) => {
            return async function ReadOrderTransactionState() {
                const response = await AdminApiContext.post('search/order-transaction', {
                    data: {
                        limit: 1,
                        filter: [
                            {
                                type: 'equals',
                                field: 'orderId',
                                value: orderId,
                            },
                        ],
                    },
                });

                expect(response.ok(), `Should find the transaction for order ${orderId}`).toBeTruthy();

                const json = await response.json() as { data: OrderTransactionState[] };
                const transaction = json.data[0];
                if (!transaction?.id || !transaction.stateId) {
                    throw new Error(`Order ${orderId} has no transaction.`);
                }

                return transaction;
            };
        };

        await use(task);
    },
});
