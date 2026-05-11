import { StorefrontPageObjects } from '@shopware-ag/acceptance-test-suite';
import type { Page, Locator } from '@shopware-ag/acceptance-test-suite';
import { PayPalExpressButton } from 'types/PayPalTypes';

export class OffCanvasCart extends StorefrontPageObjects.OffCanvasCart {
    public readonly offcanvasContainer: Locator;

    constructor(page: Page) {
        super(page);

        this.offcanvasContainer = page.locator('.offcanvas-cart-actions');
    }

    public async paypalButton(type: PayPalExpressButton): Promise<Locator> {
        const iframeLocator = this.offcanvasContainer
            .locator(`iframe.component-frame[title*="PayPal-${type}"]`)
            .first();

        const iframe = await iframeLocator.elementHandle();
        const frame = await iframe?.contentFrame();
        if (!frame) throw new Error('PayPal frame not available');
        return frame.getByRole('link');
    }
}
