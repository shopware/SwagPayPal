import SwagPaypalAbstractButtons from '../swag-paypal.abstract-buttons';

export default class SwagPayPalMarks extends SwagPaypalAbstractButtons {
    static loadingScript = false;

    static options = {
        /**
         * This option holds the client id specified in the settings
         *
         * @type string
         */
        clientId: '',

        /**
         * This option specifies the language of the PayPal button
         *
         * @type string
         */
        languageIso: 'en_GB',

        /**
         * This options specifies the currency of the PayPal button
         *
         * @type string
         */
        currency: 'EUR',

        /**
         * This options defines the payment intent
         *
         * @type string
         */
        intent: 'capture',

        /**
         * This option toggles the PayNow/Login text at PayPal
         *
         * @type boolean
         */
        commit: true,

        /**
         * This option toggles if credit card and ELV should be shown
         *
         * @type boolean
         */
        useAlternativePaymentMethods: true,
    };

    init() {
        this.createMarks();
    }

    createMarks() {
        this.createScript((paypal) => {
            paypal.Marks().render(this.el);
        });
    }
}
