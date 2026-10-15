import { test, expect } from '@fixtures/AcceptanceTest';

function unsignedJwt(payload: Record<string, unknown>): string {
    const encode = (value: unknown) => Buffer.from(JSON.stringify(value)).toString('base64url');

    return `${encode({ alg: 'HS256', typ: 'JWT' })}.${encode(payload)}.not-a-signature`;
}

test('SEC-06 A tampered return token is rejected', async ({
    RestorePayPalContext,
}) => {
    const garbage = await RestorePayPalContext('not-a-jwt')();
    const tampered = await RestorePayPalContext(unsignedJwt({ orderId: 'not-an-order' }))();

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
    const stateA = (await ReadOrderTransactionState(orderA.id)()).stateId;
    const stateB = (await ReadOrderTransactionState(orderB.id)()).stateId;

    const token = unsignedJwt({
        orderId: orderB.id,
        returnTarget: 'account-order-edit',
    });
    const url = await RestorePayPalContext(token)();

    expect(url).toContain('checkout/cart');
    expect(url).not.toContain(orderB.id);
    expect(url).not.toContain('account/order');
    expect((await ReadOrderTransactionState(orderA.id)()).stateId).toBe(stateA);
    expect((await ReadOrderTransactionState(orderB.id)()).stateId).toBe(stateB);
});
