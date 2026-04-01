import { test } from '@fixtures/AcceptanceTest';

test('Customer buys a product and pays via PayPal', { tag: ['@Storefront'] }, async ({
    ShopCustomer,
    StorefrontProductDetail,
    StorefrontCheckoutConfirm,
    StorefrontPayPalLogin,
    TestDataService,
    Login,
    AddProductToCart,
    ProceedFromProductToCheckout,
    ConfirmTermsAndConditions,
    SelectPaymentMethod,
    SelectShippingMethod,

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

    await ShopCustomer.attemptsTo(Login());
    await ShopCustomer.goesTo(StorefrontProductDetail.url(product));
    await ShopCustomer.attemptsTo(AddProductToCart(product));
    await ShopCustomer.attemptsTo(ProceedFromProductToCheckout());
    await ShopCustomer.attemptsTo(ConfirmTermsAndConditions());

    await ShopCustomer.attemptsTo(SelectPaymentMethod('PayPal'));
    await StorefrontCheckoutConfirm.page.waitForLoadState('domcontentloaded')
    await ShopCustomer.attemptsTo(SelectShippingMethod('Standard'));
    await StorefrontCheckoutConfirm.page.waitForLoadState('domcontentloaded');
    await ShopCustomer.expects(StorefrontCheckoutConfirm.paypalButton('paypal')).toBeVisible();
    
    const popupPromise = StorefrontCheckoutConfirm.page.waitForEvent('popup');
    await StorefrontCheckoutConfirm.paypalButton('paypal').click();
    const paypalPopup = await popupPromise;
    await paypalPopup.waitForLoadState('domcontentloaded');

    StorefrontPayPalLogin.setPage(paypalPopup);
    
    await ShopCustomer.expects(StorefrontPayPalLogin.eMailInput).toBeVisible();

});