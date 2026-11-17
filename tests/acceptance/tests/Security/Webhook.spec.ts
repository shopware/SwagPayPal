import { createHmac } from 'node:crypto';
import { test, expect } from '@fixtures/AcceptanceTest';

function captureCompletedBody(customId: string): Record<string, unknown> {
    return {
        event_type: 'PAYMENT.CAPTURE.COMPLETED',
        resource_type: 'capture',
        resource_version: '2.0',
        resource: {
            id: 'CAPTURE-NOT-THIS-ORDER',
            custom_id: customId,
            status: 'COMPLETED',
            final_capture: true,
        },
    };
}

async function openOrder(
    TestDataService: FixtureTypes['TestDataService'],
    ReadOrderTransactionState: FixtureTypes['ReadOrderTransactionState'],
) {
    const product = await TestDataService.createBasicProduct();
    const customer = await TestDataService.createCustomer();
    const order = await TestDataService.createOrder([{ product, quantity: 1 }], customer);
    const transaction = await ReadOrderTransactionState(order.id)();

    return { order, transaction };
}

test('SEC-03 Webhook without sw-token is rejected', async ({
    ExecutePayPalWebhook,
    ReadOrderTransactionState,
    TestDataService,
}) => {
    const { order, transaction } = await openOrder(TestDataService, ReadOrderTransactionState);
    const response = await ExecutePayPalWebhook({
        body: captureCompletedBody(transaction.id),
    })();

    expect(response.status).toBe(400);
    expect(response.body).toContain('Shopware token is invalid');
    expect((await ReadOrderTransactionState(order.id)()).technicalName).toBe(transaction.technicalName);
});

test('SEC-04 Webhook with a wrong sw-token is rejected', async ({
    ExecutePayPalWebhook,
    ReadOrderTransactionState,
    TestDataService,
}) => {
    const { order, transaction } = await openOrder(TestDataService, ReadOrderTransactionState);
    const response = await ExecutePayPalWebhook({
        token: 'wrongtokenvalue123456789012',
        body: captureCompletedBody(transaction.id),
    })();

    expect(response.status).toBe(400);
    expect(response.body).toContain('Shopware token is invalid');
    expect((await ReadOrderTransactionState(order.id)()).technicalName).toBe(transaction.technicalName);
});

test('SEC-05 A capture event for another custom id does not mark the order paid', async ({
    ExecutePayPalWebhook,
    IdProvider,
    ReadOrderTransactionState,
    ReplaceWebhookToken,
    TestDataService,
}) => {
    const token = await ReplaceWebhookToken()();

    try {
        const { order, transaction } = await openOrder(TestDataService, ReadOrderTransactionState);
        const response = await ExecutePayPalWebhook({
            token: token.value,
            body: captureCompletedBody(IdProvider.getIdPair().uuid),
        })();

        expect(response.status).toBe(400);
        expect(response.body).toContain('An error occurred during execution of webhook');
        expect(response.body).not.toContain('Shopware token is invalid');
        expect((await ReadOrderTransactionState(order.id)()).technicalName).toBe(transaction.technicalName);
    }
    finally {
        await token.restore();
    }
});

test('SEC-09 Zettle webhook with a bad signature is rejected', async ({
    ExecuteZettleWebhook,
    OpenZettleSalesChannel,
}) => {
    const channel = await OpenZettleSalesChannel()();
    const body = {
        eventName: 'InventoryBalanceChanged',
        messageUuid: 'sec-09-message',
        organizationUuid: 'sec-09-organization',
        payload: '{}',
        timestamp: new Date().toISOString(),
    };
    const validSignature = createHmac('sha256', channel.webhookSigningKey)
        .update(`${body.timestamp}.${body.payload}`)
        .digest('hex');

    expect(await ExecuteZettleWebhook(channel.salesChannelId, body)()).toBe(401);
    expect(await ExecuteZettleWebhook(channel.salesChannelId, body, 'not-a-valid-signature')()).toBe(401);
    expect(await ExecuteZettleWebhook(channel.salesChannelId, body, validSignature)()).toBe(204);
});
