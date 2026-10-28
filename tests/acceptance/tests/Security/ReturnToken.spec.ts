import { randomUUID } from 'node:crypto';
import { test, expect } from '@fixtures/AcceptanceTest';

function unsignedJwt(claims: Record<string, unknown>): string {
    const encode = (value: unknown) => Buffer.from(JSON.stringify(value)).toString('base64url');
    const now = Math.floor(Date.now() / 1000);

    return `${encode({ alg: 'HS256', typ: 'JWT' })}.${encode({
        iat: now,
        nbf: now,
        exp: now + 900,
        jti: randomUUID().replaceAll('-', ''),
        ...claims,
    })}.not-a-signature`;
}

test('SEC-06 A tampered return token is rejected', async ({
    RestorePayPalContext,
    TestDataService,
}) => {
    const salesChannelId = TestDataService.defaultSalesChannel.id;
    const garbage = await RestorePayPalContext('not-a-jwt')();
    const tampered = await RestorePayPalContext(unsignedJwt({
        contextToken: 'acceptance-context-token',
        salesChannelId,
        returnTarget: 'checkout-confirm',
    }))();

    expect(garbage).toContain('checkout/cart');
    expect(garbage).not.toContain('checkout/finish');
    expect(tampered).toContain('checkout/cart');
    expect(tampered).not.toContain('checkout/finish');
    expect(tampered).not.toContain('account/order');
});

test('SEC-07 A return token cannot complete another customer order', async ({
    RestorePayPalContext,
    ReadOrderTransactionState,
    TestDataService,
}) => {
    const product = await TestDataService.createBasicProduct();
    const customerA = await TestDataService.createCustomer();
    const customerB = await TestDataService.createCustomer();
    const orderA = await TestDataService.createOrder([{ product, quantity: 1 }], customerA);
    const orderB = await TestDataService.createOrder([{ product, quantity: 1 }], customerB);
    const stateA = (await ReadOrderTransactionState(orderA.id)()).technicalName;
    const stateB = (await ReadOrderTransactionState(orderB.id)()).technicalName;

    // The signature is invalid on purpose. A shop that skips signature checks would
    // accept these claims and redirect into customer B's order.
    const token = unsignedJwt({
        contextToken: 'acceptance-context-token',
        salesChannelId: TestDataService.defaultSalesChannel.id,
        returnTarget: 'account-order-edit',
        orderId: orderB.id,
    });
    const url = await RestorePayPalContext(token)();

    expect(url).toContain('checkout/cart');
    expect(url).not.toContain(orderB.id);
    expect(url).not.toContain('account/order');
    expect((await ReadOrderTransactionState(orderA.id)()).technicalName).toBe(stateA);
    expect((await ReadOrderTransactionState(orderB.id)()).technicalName).toBe(stateB);
});
