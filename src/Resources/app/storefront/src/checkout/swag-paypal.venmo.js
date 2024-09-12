import SwagPaypalAbstractStandalone from './swag-paypal.abstract-standalone';

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
