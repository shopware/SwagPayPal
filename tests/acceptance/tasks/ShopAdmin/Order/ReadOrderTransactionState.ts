import { test as base, expect } from '@shopware-ag/acceptance-test-suite';

export type OrderTransactionState = {
    id: string
    stateId: string
    technicalName: string
};

export type ReadOrderTransactionStateTask = (orderId: string) => () => Promise<OrderTransactionState>;

export const ReadOrderTransactionState = base.extend<FixtureTypes>({
    ReadOrderTransactionState: async ({ AdminApiContext }, use) => {
        const task: ReadOrderTransactionStateTask = (orderId) => {
            return async function ReadOrderTransactionState() {
                const response = await AdminApiContext.post('search/order-transaction', {
                    data: {
                        limit: 1,
                        associations: {
                            stateMachineState: {},
                        },
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

                const json = await response.json() as {
                    data: {
                        id: string
                        stateId: string
                        stateMachineState?: { technicalName?: string }
                    }[]
                };
                const transaction = json.data[0];
                const technicalName = transaction?.stateMachineState?.technicalName;
                if (!transaction?.id || !transaction.stateId || !technicalName) {
                    throw new Error(`Order ${orderId} has no transaction.`);
                }

                return {
                    id: transaction.id,
                    stateId: transaction.stateId,
                    technicalName,
                };
            };
        };

        await use(task);
    },
});
