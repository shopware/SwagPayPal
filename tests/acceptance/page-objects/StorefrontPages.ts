import { test as base } from '@playwright/test';
import { CheckoutConfirm } from './Storefront/Checkout/CheckoutConfirm';
import { OffCanvasCart } from './Storefront/OffCanvasCart';

export interface StorefrontPageTypes {
    StorefrontCheckoutConfirm: CheckoutConfirm
    StorefrontOffCanvasCart: OffCanvasCart;
}

export const test = base.extend<FixtureTypes>({
    StorefrontCheckoutConfirm: async ({ page }, use) => {
        await use(new CheckoutConfirm(page));
    },

    StorefrontOffCanvasCart: async ({ StorefrontPage }, use)=> {
        await use(new OffCanvasCart(StorefrontPage));
    },
});
