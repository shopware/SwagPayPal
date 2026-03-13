import { test } from '@fixtures/AcceptanceTest';

test('Check Express Buttons in Off Canvas Cart', { tag: ['@Storefront'] }, async ({
    ShopCustomer,
    StorefrontProductDetail,
    StorefrontHeader,
    StorefrontOffCanvasCart,
    TestDataService,
    AddProductToCart,
    CloseTheOffCanvasCart,

}) => {
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

    await ShopCustomer.goesTo(StorefrontProductDetail.url(product));
    await ShopCustomer.attemptsTo(AddProductToCart(product));
    
    // We need to close the off canvas cart and open it again,somehow the express buttons are not visible on the first open. But this only occurs inplaywright
    await ShopCustomer.attemptsTo(CloseTheOffCanvasCart());
    await ShopCustomer.presses(StorefrontHeader.cartTotal);
    await StorefrontHeader.cartTotal.click();
    await ShopCustomer.expects(StorefrontOffCanvasCart.offcanvasContainer).toBeVisible();
    await ShopCustomer.expects(await StorefrontOffCanvasCart.paypalButton('paypal')).toBeVisible();
    await ShopCustomer.expects(await StorefrontOffCanvasCart.paypalButton('paylater')).toBeVisible();
    
});

test('Check Express Buttons in Checkout Confirm', { tag: ['@Storefront'] }, async ({
    ShopCustomer,
    StorefrontProductDetail,
    StorefrontCheckoutConfirm,
    TestDataService,
    Login,
    AddProductToCart,
    ProceedFromProductToCheckout,
    ConfirmTermsAndConditions,
    SelectPaymentMethod,
    SelectShippingMethod,

}) => {
    const customer = await TestDataService.createCustomer();
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

    await ShopCustomer.attemptsTo(Login(customer));
    await ShopCustomer.goesTo(StorefrontProductDetail.url(product));
    await ShopCustomer.attemptsTo(AddProductToCart(product));
    await ShopCustomer.attemptsTo(ProceedFromProductToCheckout());
    await ShopCustomer.attemptsTo(ConfirmTermsAndConditions());
    await ShopCustomer.attemptsTo(SelectPaymentMethod('PayPal'));
    await StorefrontCheckoutConfirm.page.waitForLoadState('domcontentloaded');
    await ShopCustomer.attemptsTo(SelectShippingMethod('Standard'));
    await StorefrontCheckoutConfirm.page.waitForLoadState('domcontentloaded');

    await ShopCustomer.expects(StorefrontCheckoutConfirm.cartActionsContainer).toBeVisible();
    await ShopCustomer.expects(StorefrontCheckoutConfirm.paypalButton('paypal')).toBeVisible();
    await ShopCustomer.expects(StorefrontCheckoutConfirm.paypalButton('paylater')).toBeVisible();
});
