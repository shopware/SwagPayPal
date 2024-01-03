import SwagPaypalAbstractStandalone from './swag-paypal.abstract-standalone';

export default class SwagPaypalSepa extends SwagPaypalAbstractStandalone {
    static options = {
        ...super.options,
        buttonColor: 'blue',
    }

    getFundingSource(paypal) {
        return paypal.FUNDING.VENMO;
    }
}
