import type { Page, Locator } from '@shopware-ag/acceptance-test-suite';
import { StorefrontPageObjects } from '@shopware-ag/acceptance-test-suite';
import { paypalExpressButton } from '../PayPalExpressButton';

export class CheckoutRegister extends StorefrontPageObjects.CheckoutRegister {
    public readonly payPalExpressButton: Locator;

    constructor(page: Page) {
        super(page);

        this.payPalExpressButton = page.locator('[data-swag-paypal-express-button="true"]');
    }

    public paypalButton(): Promise<Locator> {
        return paypalExpressButton(this.payPalExpressButton);
    }
}
