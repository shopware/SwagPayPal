import { PageObject } from '@shopware-ag/acceptance-test-suite';
import type { Page, Locator } from 'playwright-core';

export class PayPalPayment implements PageObject {
    public readonly cartTotal: Locator;
    public readonly changeShippingAddressButton: Locator;

    public readonly seeMoreButton: Locator;

    public readonly paymentMethodRadioGroup: Locator;
    public readonly payLaterRadioGroup: Locator;

    public readonly payButton: Locator;

    public readonly page: Page;

    constructor(page: Page) {
        this.page = page;
        this.cartTotal = page.getByTestId('header-cart-total');
        this.changeShippingAddressButton = page.getByTestId('change-shipping');

        this.seeMoreButton = page.getByTestId('see-more');

        this.paymentMethodRadioGroup = page.getByTestId('pay-with');
        this.payLaterRadioGroup = page.getByTestId('pay-later');

        this.payButton = page.getByTestId('submit-button-initial');
    }

    url(): string {
        throw new Error('PayPalPayment page can\'t be called directly.');
    }
}
