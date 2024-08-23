import SwagPaypalAbstractButtons from '../swag-paypal.abstract-buttons';

export default class SwagPaypalFastlaneWatermark extends SwagPaypalAbstractButtons {
    static options = {
        ...super.options,
        /*
         * Streamline options for listing pages, overriding the ones
         * from swag-paypal.script-loading.js
         */
        useAlternativePaymentMethods: false,
        commit: false,
        scriptAwaitVisibility: true,
    };

    init() {
        this.createScript(async (paypal) => {
            const fastlane = await paypal.Fastlane();
            const component = fastlane.FastlaneWatermarkComponent();
            component.render(this.el);
        });
    }
}
