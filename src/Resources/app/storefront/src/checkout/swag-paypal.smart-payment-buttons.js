import SwagPaypalAbstractStandalone from './swag-paypal.abstract-standalone';

export default class SwagPayPalSmartPaymentButtons extends SwagPaypalAbstractStandalone {
    static options = {
        ...super.options,
        buttonColor: 'gold',
        appSwitchEnabled: false,
    };

    render(paypal) {
        const button = paypal.Buttons(this.getButtonConfig(this.getFundingSource(paypal)));

        if (!button.isEligible()) {
            return void this.handleError(this.NOT_ELIGIBLE, true, `Funding for PayPal button is not eligible (${this.getFundingSource(paypal)})`);
        }

        if (this.options.appSwitchEnabled && typeof button.hasReturned === 'function' && button.hasReturned()) {
            return button.resume();
        }

        return button.render(this.el);
    }

    getButtonConfig(fundingSource) {
        const config = super.getButtonConfig(fundingSource);

        if (this.options.appSwitchEnabled) {
            config.appSwitchWhenAvailable = true;
        }

        return config;
    }

    getFundingSource() {
        return undefined;
    }
}
