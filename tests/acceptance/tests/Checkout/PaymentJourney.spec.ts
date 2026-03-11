import { test } from '@fixtures/AcceptanceTest';

test('PaymentJourney', { tag: ['@Storefront'] }, async ({
    ShopCustomer,
    StorefrontProductDetail,
    StorefrontCheckoutConfirm,
    TestDataService,
    Login,
    AddProductToCart,

    
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
                }
            ],
        }
    );

    await ShopCustomer.attemptsTo(Login(customer));
    await ShopCustomer.goesTo(StorefrontProductDetail.url(product));
    await ShopCustomer.attemptsTo(AddProductToCart(product));
    
    throw new Error('Express buttons are currently not visible in the off canvas cart, needs to be fixed before we can proceed with the rest of the test.');    
});