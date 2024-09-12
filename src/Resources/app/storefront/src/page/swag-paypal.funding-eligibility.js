import HttpClient from 'src/service/http-client.service';
import SwagPaypalAbstractButtons from '../swag-paypal.abstract-buttons';

export default class SwagPayPalFundingEligibility extends SwagPaypalAbstractButtons {
    static fundingSources = [
        'CARD',
        'SEPA',
        'VENMO',
        'PAYLATER',
    ];

    static options = {
        ...super.options,

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

        /*
         * Streamline options for listing pages, overriding the ones
         * from swag-paypal.script-loading.js
         */
        useAlternativePaymentMethods: true,
        commit: false,
        scriptAwaitVisibility: true,
        partOfDomContentLoading: false,
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

        try {
            if (!window.ApplePaySession?.supportsVersion(4) || !window.ApplePaySession?.canMakePayments()) {
                unavailable.push('APPLEPAY');
            }
        } catch (e) {
            unavailable.push('APPLEPAY');
        }

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
