import { test, expect } from '@fixtures/AcceptanceTest';

test('E2E-14 As an admin, I can refund a PayPal payment from the order', { tag: ['@Settings'] }, async ({
    ShopAdmin,
    AdminOrderDetail,
    AttachPayPalCapture,
    CaptureSandboxPayPalOrder,
    ReadOrderTransactionState,
    ReadPaymentMethodId,
    TestDataService,
}) => {
    test.setTimeout(60_000);

    const product = await TestDataService.createBasicProduct(
        {
            price: [
                {
                    currencyId: TestDataService.defaultCurrencyId,
                    gross: 50,
                    linked: false,
                    net: 42.01,
                },
            ],
        },
    );
    const customer = await TestDataService.createCustomer();
    const order = await TestDataService.createOrder([{ product, quantity: 1 }], customer);
    const payPalPaymentMethodId = await ReadPaymentMethodId('swag_paypal_paypal')();
    const paypalOrderId = await CaptureSandboxPayPalOrder()();
    const transaction = await ReadOrderTransactionState(order.id)();

    await AttachPayPalCapture(transaction.id, payPalPaymentMethodId, paypalOrderId)();

    await ShopAdmin.goesTo(AdminOrderDetail.url(order.id, 'paypal'));
    await ShopAdmin.expects(AdminOrderDetail.payPalTab).toBeVisible();
    await ShopAdmin.expects(AdminOrderDetail.payPalRefundButton).toBeEnabled();
    await AdminOrderDetail.payPalRefundButton.click();
    await ShopAdmin.expects(AdminOrderDetail.payPalRefundConfirmButton).toBeEnabled();
    // The Symfony profiler toolbar sits over the dialog action in the dev shop.
    await AdminOrderDetail.page.addStyleTag({
        content: '.sf-toolbar { display: none !important; pointer-events: none !important; }',
    });
    await AdminOrderDetail.payPalRefundConfirmButton.click();
    await ShopAdmin.expects(AdminOrderDetail.payPalRefundSuccess).toBeVisible();
    await expect.poll(async () => (await ReadOrderTransactionState(order.id)()).technicalName)
        .toMatch(/^(refunded|refunded_partially)$/);
});
