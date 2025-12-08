import PayPalPluginError from '../base/paypal-plugin.error';
import SwagPaypalBase, { type SwagPaypalBaseOptions } from '../base/swag-paypal.base';

interface SwagPayPalEligibilityOptions extends SwagPaypalBaseOptions {
    filteredPaymentMethods: string[];
    methodEligibilityUrl: string;
}

export default class SwagPayPalEligibility extends SwagPaypalBase {
    declare options: SwagPayPalEligibilityOptions;
    static options: SwagPayPalEligibilityOptions = {
        ...SwagPaypalBase.options,

        /**
         * Previously filtered payment methods
         */
        filteredPaymentMethods: [],

        /**
         * The url to filter payment methods
         */
        methodEligibilityUrl: '',
    };

    fundingSources: Record<string, PayPalCoreJS.FundingSource> = {
        CARD: 'advanced_cards',
        // SEPA: 'sepa',
        VENMO: 'venmo',
        PAYLATER: 'paylater',
    };

    protected get metadata(): { components: [] } {
        return {
            components: [],
        };
    }

    protected async setup(): Promise<void> {
        const eligibleMethods = await this.findEligibleMethods();

        const unavailable = Object.entries(this.fundingSources)
            .filter(([, source]) => !eligibleMethods.isEligible(source))
            .map(([key]) => key)
            .sort();

        if (unavailable.join(',') === this.options.filteredPaymentMethods.sort().join(',')) {
            return;
        }

        const response = await fetch(this.options.methodEligibilityUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ paymentMethods: unavailable }),
        });

        if (!response.ok) {
            throw await PayPalPluginError.api('method-eligibility', response);
        }
    }
}
