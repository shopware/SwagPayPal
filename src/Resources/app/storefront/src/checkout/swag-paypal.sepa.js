import SwagPaypalAbstractStandalone from './swag-paypal.abstract-standalone';

export default class SwagPaypalSepa extends SwagPaypalAbstractStandalone {
    getFundingSource(paypal) {
        return paypal.FUNDING.SEPA;
    }
}
