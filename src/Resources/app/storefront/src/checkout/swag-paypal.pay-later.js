import SwagPaypalAbstractStandalone from './swag-paypal.abstract-standalone';

/**
 * @deprecated tag:v11.0.0 - Will be removed and is replaced by `payment/swag-paypal.checkout.pay-later.ts`
 */
export default class SwagPaypalSepa extends SwagPaypalAbstractStandalone {
    static options = {
        ...super.options,
        buttonColor: 'gold',
    };

    getFundingSource(paypal) {
        return paypal.FUNDING.PAYLATER;
    }
}
