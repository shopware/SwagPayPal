import type { Locator, Page } from '@shopware-ag/acceptance-test-suite';
import { PageObject } from '@shopware-ag/acceptance-test-suite';

export class PayPalDisputesDetail implements PageObject {
    public readonly page: Page;

    public readonly openDisputeButton: Locator;
    public readonly openOrderButton: Locator;

    constructor(page: Page) {
        this.page = page;

        this.openDisputeButton = page.getByRole('link', { name: 'Open dispute' });
        this.openOrderButton = page.getByRole('link', { name: 'Open order' });
    }

    url(id: string) {
        return `#/swag/paypal/disputes/detail/${id}`;
    }
}
