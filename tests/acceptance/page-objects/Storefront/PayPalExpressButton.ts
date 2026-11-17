import type { Locator } from '@shopware-ag/acceptance-test-suite';
import type { PayPalExpressButton } from 'types/PayPalTypes';

/**
 * The non-v6 express markup is a container that the storefront script replaces with a PayPal iframe.
 * PayPal shows a placeholder frame first, so clicks only work once the component frame is `visible`.
 */
export function paypalExpressButton(scope: Locator, type: PayPalExpressButton = 'paypal'): Locator {
    return scope
        .locator(`iframe.component-frame.visible[title*="PayPal-${type}"]`)
        .first()
        .contentFrame()
        .getByRole('link');
}
