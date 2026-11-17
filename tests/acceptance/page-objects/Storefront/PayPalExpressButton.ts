import type { Locator } from '@shopware-ag/acceptance-test-suite';
import type { PayPalExpressButton } from 'types/PayPalTypes';

/**
 * The non-v6 express markup is a container that the storefront script replaces with a PayPal iframe.
 */
export function paypalExpressButton(scope: Locator, type: PayPalExpressButton = 'paypal'): Locator {
    return scope
        .locator(`iframe.component-frame[title*="PayPal-${type}"]`)
        .first()
        .contentFrame()
        .getByRole('link');
}
