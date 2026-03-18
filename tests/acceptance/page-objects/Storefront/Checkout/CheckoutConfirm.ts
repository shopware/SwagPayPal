import type { Page, Locator } from '@shopware-ag/acceptance-test-suite';
import { StorefrontPageObjects } from '@shopware-ag/acceptance-test-suite';
import type { PayPalExpressButton } from '../../../types/PayPalTypes';

export class CheckoutConfirm extends StorefrontPageObjects.CheckoutConfirm {
    public readonly paymentACDC: Locator;
    public readonly paymentApplePay: Locator;
    public readonly paymentGooglePay: Locator;
    public readonly paymentP24: Locator;
    public readonly paymentPayPal: Locator;
    public readonly paymentPUI: Locator;
    public readonly paymentSepa: Locator;

    public readonly cartActionsContainer: Locator;

    constructor(page: Page) {
        super(page);

        this.paymentACDC = page.getByLabel('Credit or debit card');
        this.paymentApplePay = page.getByLabel('Apple Pay');
        this.paymentGooglePay = page.getByLabel('Google Pay');
        this.paymentP24 = page.getByLabel('Przelewy24');
        this.paymentPayPal = page.getByLabel('PayPal');
        this.paymentPUI = page.getByLabel('Pay upon invoice');
        this.paymentSepa = page.getByLabel('SEPA direct debit');

        this.cartActionsContainer = page.locator('.checkout-aside-action');
    }

    public paypalButton(type: PayPalExpressButton): Locator {
        const frame = this.cartActionsContainer
            .frameLocator('iframe.component-frame[title="PayPal"]');

        return frame.locator(`[data-funding-source="${type}"]`);
    }
}
