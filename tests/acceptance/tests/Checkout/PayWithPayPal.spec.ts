import { test } from '@fixtures/AcceptanceTest';

test('Customer buys a product and pays via PayPal', { tag: ['@Storefront'] }, async ({
    ShopCustomer,
    StorefrontProductDetail,
    StorefrontCheckoutConfirm,
    StorefrontPayPalLogin,
    StorefrontPayPalPayment,
    StorefrontCheckoutFinish,
    TestDataService,
    PayPalDataProvider,
    Login,
    AddProductToCart,
    ProceedFromProductToCheckout,
    ConfirmTermsAndConditions,
    SelectPaymentMethod,
    SelectShippingMethod,

}) => {
    test.setTimeout(30_000);
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

    await ShopCustomer.attemptsTo(Login(customer));
    await ShopCustomer.goesTo(StorefrontProductDetail.url(product));
    await ShopCustomer.attemptsTo(AddProductToCart(product));
    await ShopCustomer.attemptsTo(ProceedFromProductToCheckout());
    await ShopCustomer.attemptsTo(ConfirmTermsAndConditions());
    await ShopCustomer.attemptsTo(SelectPaymentMethod('PayPal'));
    await StorefrontCheckoutConfirm.page.waitForLoadState('domcontentloaded');
    await ShopCustomer.attemptsTo(SelectShippingMethod('Standard'));
    await StorefrontCheckoutConfirm.page.waitForLoadState('domcontentloaded');
    await ShopCustomer.expects(StorefrontCheckoutConfirm.paypalButton('paypal')).toBeVisible();

    const popupPromise = StorefrontCheckoutConfirm.page.waitForEvent('popup');
    await StorefrontCheckoutConfirm.paypalButton('paypal').click();
    const paypalPopup = await popupPromise;
    await paypalPopup.waitForLoadState('domcontentloaded');

    StorefrontPayPalLogin.setPage(paypalPopup);
    await ShopCustomer.expects(StorefrontPayPalLogin.eMailInput).toBeVisible();
    await StorefrontPayPalLogin.eMailInput.fill(PayPalDataProvider.get('CUSTOMER_ID'));
    await StorefrontPayPalLogin.nextButton.click();
    await StorefrontPayPalLogin.page.waitForLoadState('domcontentloaded');
    await ShopCustomer.expects(StorefrontPayPalLogin.passwordInput).toBeVisible();
    await StorefrontPayPalLogin.passwordInput.fill(PayPalDataProvider.get('CUSTOMER_PASSWORD'));
    await StorefrontPayPalLogin.loginButton.click();

    StorefrontPayPalPayment.setPage(paypalPopup);
    await StorefrontPayPalPayment.page.waitForLoadState('domcontentloaded');
    await ShopCustomer.expects(StorefrontPayPalPayment.payButton).toBeVisible();
    await StorefrontPayPalPayment.payButton.click();

    await StorefrontCheckoutFinish.page.waitForLoadState('domcontentloaded');
    await ShopCustomer.expects(StorefrontCheckoutFinish.headline).toBeVisible();
});
