import type { Page } from '@shopware-ag/acceptance-test-suite';
import { PageObject } from '@shopware-ag/acceptance-test-suite';

export class PayPalDisputesListing implements PageObject {
    public readonly page: Page;

    constructor(page: Page) {
        this.page = page;
    }

    url() {
        return '#/swag/paypal/disputes/index';
    }
}
