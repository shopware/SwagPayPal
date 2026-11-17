import type { Page, Locator, FixtureTypes } from '@shopware-ag/acceptance-test-suite';
import { AdminPageObjects } from '@shopware-ag/acceptance-test-suite';

export class OrderDetail extends AdminPageObjects.OrderDetail {
    public readonly payPalTab: Locator;
    public readonly payPalRefundButton: Locator;
    public readonly payPalRefundConfirmButton: Locator;
    public readonly payPalRefundSuccess: Locator;

    constructor(page: Page, instanceMeta: FixtureTypes['InstanceMeta']) {
        super(page, instanceMeta);

        this.payPalTab = page.getByRole('tab', { name: 'PayPal', exact: true });
        this.payPalRefundButton = page.getByRole('button', { name: 'Create a new refund' });
        this.payPalRefundConfirmButton = page.getByRole('dialog', { name: 'New refund' }).getByRole('button', { name: 'Execute' });
        this.payPalRefundSuccess = page.getByText('Your refund was successful.');
    }
}
