import { PageObject } from '@shopware-ag/acceptance-test-suite';
import type { Page, Locator } from 'playwright-core';

export class PayPalPayment implements PageObject {
    public cartTotal!: Locator;
    public changeShippingAddressButton!: Locator;

    public seeMoreButton!: Locator;

    public paymentMethodRadioGroup!: Locator;
    public payLaterRadioGroup!: Locator;

    public payButton!: Locator;

    public page!: Page;

    constructor(page: Page) {
        this.setPage(page);
    }

    setPage(page: Page): void {
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
