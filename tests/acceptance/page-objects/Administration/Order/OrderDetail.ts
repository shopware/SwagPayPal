import type { Page, Locator } from '@shopware-ag/acceptance-test-suite';
import { AdminPageObjects } from '@shopware-ag/acceptance-test-suite';

export class OrderDetail extends AdminPageObjects.OrderDetail {
    public readonly payPalTab: Locator;
    public readonly payPalRefundButton: Locator;

    constructor(page: Page) {
        super(page);

        this.payPalTab = page.getByRole('link', { name: 'PayPal' });
        this.payPalRefundButton = page.getByRole('button', { name: 'Create a new refund' });
    }
}
