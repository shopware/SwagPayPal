import SwagPaypalAbstractButtons from '../swag-paypal.abstract-buttons';

export default class SwagPayPalMarks extends SwagPaypalAbstractButtons {
    static options = {
        /**
         * This option holds the client id specified in the settings
         *
         * @type string
         */
        clientId: '',

        /**
         * This option toggles if credit card and ELV should be shown
         *
         * @type boolean
         */
        useAlternativePaymentMethods: true
    };

    init() {
        this.paypal = null;
        this.createMarks();
    }

    createMarks() {
        this.createScript(() => {
            const paypal = window.paypal;
            paypal.Marks().render(this.el);
        });
    }
}
