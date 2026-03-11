import { test } from '@fixtures/AcceptanceTest';

test('Check Express Buttons in Off Canvas Cart', { tag: ['@Storefront'] }, 
    async ({
        ShopCustomer,
        StorefrontProductDetail,
        StorefrontCheckoutConfirm,
        StorefrontCheckoutFinish,
        TestDataService,
        Login,
        AddProductToCart,
        ConfirmTermsAndConditions,
        SelectPaymentMethod,
        SelectShippingMethod,
        ProceedFromProductToCheckout,
    
    }) => {
        const product = await TestDataService.createBasicProduct();

        await ShopCustomer.attemptsTo(Login());

        await ShopCustomer.goesTo(StorefrontProductDetail.url(product));
        await ShopCustomer.expects(StorefrontProductDetail.page).toHaveTitle(
            `${product.translated.name} | ${product.productNumber}`
        );

        await ShopCustomer.attemptsTo(AddProductToCart(product));
        await ShopCustomer.attemptsTo(ProceedFromProductToCheckout());
        await ShopCustomer.attemptsTo(ConfirmTermsAndConditions());

        await ShopCustomer.attemptsTo(SelectPaymentMethod('PayPal'));
        await ShopCustomer.attemptsTo(SelectShippingMethod('Standard'));
        await ShopCustomer.expects(await StorefrontCheckoutConfirm.paypalButton('paypal')).toBeVisible();
        await ShopCustomer.expects(await StorefrontCheckoutConfirm.paypalButton('paylater')).toBeVisible();
});