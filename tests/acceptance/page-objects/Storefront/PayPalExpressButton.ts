import type { Locator } from '@shopware-ag/acceptance-test-suite';
import type { PayPalExpressButton } from 'types/PayPalTypes';

/**
 * The non-v6 express markup is a container that the storefront script replaces with a PayPal iframe.
 */
export async function paypalExpressButton(scope: Locator, type: PayPalExpressButton = 'paypal'): Promise<Locator> {
    const iframeLocator = scope
        .locator(`iframe.component-frame[title*="PayPal-${type}"]`)
        .first();

    const iframe = await iframeLocator.elementHandle();
    const frame = await iframe?.contentFrame();
    if (!frame) {
        throw new Error('PayPal frame not available');
    }

    return frame.getByRole('link');
}
