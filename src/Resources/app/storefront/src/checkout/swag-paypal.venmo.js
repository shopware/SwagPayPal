import SwagPaypalAbstractStandalone from './swag-paypal.abstract-standalone';

/**
 * @deprecated tag:v11.0.0 - Will be removed and is replaced by `payment/swag-paypal.checkout.venmo.ts`
 */
export default class SwagPaypalVenmo extends SwagPaypalAbstractStandalone {
    static product = 'venmo';
    static options = {
        ...super.options,
        buttonColor: 'blue',
    };

    getFundingSource(paypal) {
        return paypal.FUNDING.VENMO;
    }
}
