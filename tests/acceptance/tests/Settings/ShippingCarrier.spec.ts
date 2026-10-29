import { test } from '@fixtures/AcceptanceTest';

test('As an admin, I can select a standard carrier that is used to be displayed by PayPal', { tag: ['@Settings'] }, async ({
    ShopAdmin,
    AdminShippingDetail,
    TestDataService,
    SetStandardCarrier,
}) => {
    const shipping = await TestDataService.createBasicShippingMethod();

    await ShopAdmin.goesTo(AdminShippingDetail.url(shipping.id));
    await ShopAdmin.attemptsTo(SetStandardCarrier('Canada Post'));
    await ShopAdmin.expects(AdminShippingDetail.standardShippingCarrierCard).toContainText('Canada Post');
});
