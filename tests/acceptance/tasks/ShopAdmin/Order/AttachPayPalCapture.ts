import { test as base, expect } from '@shopware-ag/acceptance-test-suite';

export type AttachPayPalCaptureTask = (
    transactionId: string,
    paymentMethodId: string,
    paypalOrderId: string,
) => () => Promise<void>;

export const AttachPayPalCapture = base.extend<FixtureTypes>({
    AttachPayPalCapture: async ({ AdminApiContext }, use) => {
        const task: AttachPayPalCaptureTask = (transactionId, paymentMethodId, paypalOrderId) => {
            return async function AttachPayPalCapture() {
                const linkResponse = await AdminApiContext.patch(`order-transaction/${transactionId}`, {
                    data: {
                        paymentMethodId,
                        customFields: {
                            swag_paypal_order_id: paypalOrderId,
                        },
                    },
                });
                expect(linkResponse.ok(), 'Should link the captured PayPal order to the Shopware transaction').toBeTruthy();

                const paidResponse = await AdminApiContext.post(`./_action/order_transaction/${transactionId}/state/paid`, {});
                expect(paidResponse.ok(), 'Should mark the transaction paid so a refund is allowed').toBeTruthy();
            };
        };

        await use(task);
    },
});
