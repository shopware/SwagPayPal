import { test } from '@fixtures/AcceptanceTest';
import { paypalExpressButton } from '@page-objects/Storefront/PayPalExpressButton';

// The PayPal order is only created once the product is in the cart.
const CREATE_ORDER_URL = '**/paypal/express/create-order?addProductToCart=1';

test('Express Checkout on the product detail page creates the PayPal order without the offcanvas cart', { tag: ['@Storefront'] }, async ({
    ShopCustomer,
    StorefrontProductDetail,
    TestDataService,
}) => {
    await TestDataService.setSystemConfig({ 'core.cart.openOffcanvasAfterAddToCart': false });
    const product = await TestDataService.createBasicProduct();

    await ShopCustomer.goesTo(StorefrontProductDetail.url(product));

    // PayPal ignores clicks until the plugin's `onInit` has enabled the button.
    const expressButton = paypalExpressButton(StorefrontProductDetail.page.locator('[data-swag-paypal-express-button]'));
    await ShopCustomer.expects(expressButton).toHaveAttribute('aria-disabled', 'false');

    const createOrderRequest = StorefrontProductDetail.page.waitForRequest(CREATE_ORDER_URL);
    await expressButton.click();
    await createOrderRequest;
});

test('Express Checkout with SDK v6 on the product detail page creates the PayPal order without the offcanvas cart', { tag: ['@Storefront'] }, async ({
    ShopCustomer,
    StorefrontProductDetail,
    TestDataService,
}) => {
    await TestDataService.setSystemConfig({
        'core.cart.openOffcanvasAfterAddToCart': false,
        'SwagPayPal.settings.sdkV6Enabled': true,
    });
    const product = await TestDataService.createBasicProduct();

    await ShopCustomer.goesTo(StorefrontProductDetail.url(product));

    const createOrderRequest = StorefrontProductDetail.page.waitForRequest(CREATE_ORDER_URL);
    await StorefrontProductDetail.page.locator('paypal-button[data-swag-paypal-express-paypal]').click();
    await createOrderRequest;
});
