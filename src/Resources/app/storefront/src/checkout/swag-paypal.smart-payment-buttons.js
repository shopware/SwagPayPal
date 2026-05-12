import SwagPaypalAbstractStandalone from './swag-paypal.abstract-standalone';

export default class SwagPayPalSmartPaymentButtons extends SwagPaypalAbstractStandalone {
    static options = {
        ...super.options,
        buttonColor: 'gold',
    };

    getFundingSource() {
        return undefined;
    }
}
