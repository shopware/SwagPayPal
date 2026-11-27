import { test } from '@fixtures/AcceptanceTest';
import { paypalExpressButton } from '@page-objects/Storefront/PayPalExpressButton';

// The PayPal order is only created once the product is in the cart, and creating it fails for an empty cart.
const CREATE_ORDER_URL = '**/paypal/express/create-order**';

// Express Checkout waits for `openOffCanvasCart` with the offcanvas cart and for `addToCartWithoutOffcanvas` without it.
for (const openOffcanvasAfterAddToCart of [true, false]) {
    const offcanvasCart = openOffcanvasAfterAddToCart ? 'with the offcanvas cart' : 'without the offcanvas cart';

    test(`Express Checkout on the product detail page creates the PayPal order ${offcanvasCart}`, { tag: ['@Storefront'] }, async ({
        ShopCustomer,
        StorefrontProductDetail,
        TestDataService,
    }) => {
        await TestDataService.setSystemConfig({ 'core.cart.openOffcanvasAfterAddToCart': openOffcanvasAfterAddToCart });
        const product = await TestDataService.createBasicProduct();

        await ShopCustomer.goesTo(StorefrontProductDetail.url(product));

        // PayPal ignores clicks until the plugin's `onInit` has enabled the button.
        const expressButton = paypalExpressButton(StorefrontProductDetail.page.locator('[data-swag-paypal-express-button]'));
        await ShopCustomer.expects(expressButton).toHaveAttribute('aria-disabled', 'false');

        const createOrderResponse = StorefrontProductDetail.page.waitForResponse(CREATE_ORDER_URL);
        await expressButton.click();
        ShopCustomer.expects((await createOrderResponse).ok()).toBeTruthy();
    });

    test(`Express Checkout with SDK v6 on the product detail page creates the PayPal order ${offcanvasCart}`, { tag: ['@Storefront'] }, async ({
        ShopCustomer,
        StorefrontProductDetail,
        TestDataService,
    }) => {
        await TestDataService.setSystemConfig({
            'core.cart.openOffcanvasAfterAddToCart': openOffcanvasAfterAddToCart,
            'SwagPayPal.settings.sdkV6Enabled': true,
        });
        const product = await TestDataService.createBasicProduct();

        await ShopCustomer.goesTo(StorefrontProductDetail.url(product));

        const createOrderResponse = StorefrontProductDetail.page.waitForResponse(CREATE_ORDER_URL);
        await StorefrontProductDetail.page.locator('paypal-button[data-swag-paypal-express-paypal]').click();
        ShopCustomer.expects((await createOrderResponse).ok()).toBeTruthy();
    });
}
