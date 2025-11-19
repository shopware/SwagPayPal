import SwagPaypalBase, { type SwagPaypalBaseOptions } from '../base/swag-paypal.base';
import PayPalSdkLoader from '../helper/paypal-sdk-loader.helper';

interface SwagPayPalEligibilityOptions extends SwagPaypalBaseOptions {
    filteredPaymentMethods: string[];
    methodEligibilityUrl: string;
}

export default class SwagPayPalEligibility extends SwagPaypalBase {
    static fundingSources: Record<string, PayPalCoreJS.FundingSource> = {
        CARD: 'advanced_cards',
        // SEPA: 'sepa',
        VENMO: 'venmo',
        PAYLATER: 'paylater',
    };

    static options: SwagPayPalEligibilityOptions = {
        ...this.options,

        /**
         * Previously filtered payment methods
         */
        filteredPaymentMethods: [],

        /**
         * The url to filter payment methods
         */
        methodEligibilityUrl: '',

        partOfDomContentLoading: false,
    };

    protected async prepare(): Promise<void> {
        const eligibleMethods = await PayPalSdkLoader.findEligibleMethods({
            currencyCode: this.options.currency,
        });

        const unavailable = Object.entries(SwagPayPalEligibility.fundingSources)
            .filter(async ([, source]) => !eligibleMethods.isEligible(source))
            .map(([key]) => key);

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

        const response = await fetch(this.options.methodEligibilityUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ paymentMethods: unavailable }),
        });

        if (response.ok) {
            this.options.filteredPaymentMethods = await response.json();
        }
    }
}
