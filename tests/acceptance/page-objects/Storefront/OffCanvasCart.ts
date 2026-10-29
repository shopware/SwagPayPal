import { StorefrontPageObjects } from '@shopware-ag/acceptance-test-suite';
import type { Page, Locator } from '@shopware-ag/acceptance-test-suite';
import type { PayPalExpressButton } from 'types/PayPalTypes';
import { paypalExpressButton } from './PayPalExpressButton';

export class OffCanvasCart extends StorefrontPageObjects.OffCanvasCart {
    public readonly offcanvasContainer: Locator;

    constructor(page: Page) {
        super(page);

        this.offcanvasContainer = page.locator('.offcanvas-cart-actions');
    }

    public paypalButton(type: PayPalExpressButton): Locator {
        return paypalExpressButton(this.offcanvasContainer, type);
    }
}
