import { test as base } from '@playwright/test';
import { CheckoutConfirm } from './Storefront/Checkout/CheckoutConfirm';
import { OffCanvasCart } from './Storefront/OffCanvasCart';
import { PayPalLogin } from './Storefront/Checkout/PayPalLogin';
import { PayPalPayment } from './Storefront/Checkout/PayPalPayment';

export interface StorefrontPageTypes {
    StorefrontCheckoutConfirm: CheckoutConfirm
    StorefrontOffCanvasCart: OffCanvasCart
    StorefrontPayPalLogin: PayPalLogin
    StorefrontPayPalPayment: PayPalPayment
}

export const test = base.extend<FixtureTypes>({
    StorefrontCheckoutConfirm: async ({ StorefrontPage }, use) => {
        await use(new CheckoutConfirm(StorefrontPage));
    },

    StorefrontOffCanvasCart: async ({ StorefrontPage }, use) => {
        await use(new OffCanvasCart(StorefrontPage));
    },

    StorefrontPayPalLogin: async ({ StorefrontPage }, use) => {
        await use(new PayPalLogin(StorefrontPage));
    },

    StorefrontPayPalPayment: async ({ StorefrontPage }, use) => {
        await use(new PayPalPayment(StorefrontPage));
    },
});
