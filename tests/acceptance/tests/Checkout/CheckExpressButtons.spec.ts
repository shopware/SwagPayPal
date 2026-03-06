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
                }
            ],
        }
    );

    await ShopCustomer.goesTo(StorefrontProductDetail.url(product));
    await ShopCustomer.attemptsTo(AddProductToCart(product));
    await ShopCustomer.attemptsTo(CloseTheOffCanvasCart());
    await StorefrontHeader.cartTotal.click();
    await ShopCustomer.expects(StorefrontOffCanvasCart.offcanvasContainer).toBeVisible();
    await ShopCustomer.expects(await StorefrontOffCanvasCart.paypalButton('paypal')).toBeVisible();
    await ShopCustomer.expects(await StorefrontOffCanvasCart.paypalButton('paylater')).toBeVisible();
});