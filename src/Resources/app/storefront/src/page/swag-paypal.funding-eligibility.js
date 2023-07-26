import HttpClient from 'src/service/http-client.service';
import SwagPaypalAbstractButtons from '../swag-paypal.abstract-buttons';

export default class SwagPayPalFundingEligibility extends SwagPaypalAbstractButtons {
    static fundingSources = [
        'CARD',
        'SEPA',
        'VENMO',
        'PAYLATER',
    ]

    static options = {
        /**
         * This option holds the client id specified in the settings
         *
         * @type string
         */
        clientId: '',

        /**
         * This option holds the merchant id specified in the settings
         *
         * @type string
         */
        merchantPayerId: '',

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
         * Previously filtered payment methods
         *
         * @type string[]
         */
        filteredPaymentMethods: [],

        /**
         * The url to filter payment methods
         *
         * @type string
         */
        methodEligibilityUrl: '',
    };

    init() {
        this._client = new HttpClient();

        this.createScript((paypal) => {
            this.checkFunding(paypal);
        });
    }

    checkFunding(paypal) {
        const unavailable = this.constructor.fundingSources.filter((sourceName) => {
            return !paypal.isFundingEligible(paypal.FUNDING[sourceName]);
        });

        if (unavailable.sort().join(',') === this.options.filteredPaymentMethods.sort().join(',')) {
            return;
        }

        this.updateMethodEligibility(unavailable);
    }

    updateMethodEligibility(paymentMethods) {
        this._client.post(this.options.methodEligibilityUrl, JSON.stringify({ paymentMethods }), () => {
            this.options.filteredPaymentMethods = paymentMethods;
        });
    }
}
