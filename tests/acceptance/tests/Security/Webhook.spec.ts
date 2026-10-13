import { test, expect } from '@fixtures/AcceptanceTest';

const captureCompleted = {
    event_type: 'PAYMENT.CAPTURE.COMPLETED',
};

test('SEC-03 Webhook without sw-token is rejected', async ({
    ExecutePayPalWebhook,
    ReplaceWebhookToken,
}) => {
    const token = await ReplaceWebhookToken()();

    try {
        const response = await ExecutePayPalWebhook({
            body: captureCompleted,
        })();

        expect(response.status).toBe(400);
        expect(response.body).toContain('Shopware token is invalid');
    }
    finally {
        await token.restore();
    }
});

test('SEC-04 Webhook with a wrong sw-token is rejected', async ({
    ExecutePayPalWebhook,
    ReplaceWebhookToken,
}) => {
    const token = await ReplaceWebhookToken()();

    try {
        const response = await ExecutePayPalWebhook({
            token: 'wrongtokenvalue123456789012',
            body: captureCompleted,
        })();

        expect(response.status).toBe(400);
        expect(response.body).toContain('Shopware token is invalid');
    }
    finally {
        await token.restore();
    }
});

test('SEC-05 A capture event for another custom id does not mark the order paid', async ({
    ExecutePayPalWebhook,
    IdProvider,
    ReadOrderTransactionState,
    ReplaceWebhookToken,
    TestDataService,
}) => {
    const token = await ReplaceWebhookToken()();
    const product = await TestDataService.createBasicProduct();
    const customer = await TestDataService.createCustomer();
    const order = await TestDataService.createOrder([{ product, quantity: 1 }], customer);
    const transaction = await ReadOrderTransactionState(order.id)();
    const otherCustomId = IdProvider.getIdPair().uuid;

    try {
        const response = await ExecutePayPalWebhook({
            token: token.value,
            body: {
                event_type: 'PAYMENT.CAPTURE.COMPLETED',
                resource_type: 'capture',
                resource_version: '2.0',
                resource: {
                    id: 'CAPTURE-NOT-THIS-ORDER',
                    custom_id: otherCustomId,
                    status: 'COMPLETED',
                    final_capture: true,
                },
            },
        })();

        expect(response.status).toBe(400);
        expect((await ReadOrderTransactionState(order.id)()).stateId).toBe(transaction.stateId);
    }
    finally {
        await token.restore();
    }
});

test('SEC-09 Zettle webhook with a bad signature is rejected', async ({
    ExecuteZettleWebhook,
    OpenZettleSalesChannel,
    ReadProductStock,
    TestDataService,
}) => {
    const product = await TestDataService.createBasicProduct({ stock: 4 });
    const channel = await OpenZettleSalesChannel()();
    const body = {
        eventName: 'InventoryBalanceChanged',
        messageUuid: 'sec-09-message',
        organizationUuid: 'sec-09-organization',
        payload: '{}',
        timestamp: '2020-01-01T00:00:00.000Z',
    };

    expect(await ExecuteZettleWebhook(channel.salesChannelId, body)()).toBe(401);
    expect(await ExecuteZettleWebhook(channel.salesChannelId, body, 'not-a-valid-signature')()).toBe(401);
    expect(await ReadProductStock(product.id)()).toBe(4);
});
