import type { Page, Locator } from '@shopware-ag/acceptance-test-suite';
import { StorefrontPageObjects } from '@shopware-ag/acceptance-test-suite';
import { paypalExpressButton } from './PayPalExpressButton';

export class ProductDetail extends StorefrontPageObjects.ProductDetail {
    public readonly payPalExpressButton: Locator;

    constructor(page: Page) {
        super(page);

        this.payPalExpressButton = page.locator('[data-swag-paypal-express-button="true"]');
    }

    public paypalButton(): Promise<Locator> {
        return paypalExpressButton(this.payPalExpressButton);
    }
}
