import { test } from '@fixtures/AcceptanceTest';
import { paypalExpressButton } from '@page-objects/Storefront/PayPalExpressButton';

test('Express Checkout on the product detail page creates the PayPal order without the offcanvas cart', { tag: ['@Storefront'] }, async ({
    ShopCustomer,
    StorefrontProductDetail,
    TestDataService,
}) => {
    await TestDataService.setSystemConfig({ 'core.cart.openOffcanvasAfterAddToCart': false });
    const product = await TestDataService.createBasicProduct();

    await ShopCustomer.goesTo(StorefrontProductDetail.url(product));

    // The PayPal order is only created once the product is in the cart.
    const createOrderRequest = StorefrontProductDetail.page.waitForRequest('**/paypal/express/create-order**');
    await paypalExpressButton(StorefrontProductDetail.page.locator('[data-swag-paypal-express-button]')).click();
    await createOrderRequest;
});
