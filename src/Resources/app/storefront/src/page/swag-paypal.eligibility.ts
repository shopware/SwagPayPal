import type { PayPalNamespace } from '@paypal/paypal-js/types';
import PayPalPluginError from '../base/paypal-plugin.error';
import SwagPaypalBase, { type SwagPaypalBaseOptions } from '../base/swag-paypal.base';
import DependencyHelper from '../helper/dependency.helper';

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

    fundingSources: Record<string, PayPalCoreJS.FundingSource | 'sepa'> = {
        CARD: 'advanced_cards',
        SEPA: 'sepa',
        VENMO: 'venmo',
        PAYLATER: 'paylater',
    };

    paypalV5: PayPalNamespace | null = null;

    protected get metadata(): { components: [] } {
        return {
            components: [],
        };
    }

    protected async beforeSetup(): Promise<void> {
        const [_, paypalV5] = await Promise.all([
            super.beforeSetup(),
            DependencyHelper.loadPayPalV5ForEligibility({
                clientId: this.options.clientId!,
                merchantId: this.options.merchantPayerId!,
                dataPartnerAttributionId: this.options.partnerAttributionId,
                locale: this.options.languageIso,
                currency: this.options.currency,
            }).catch(() => null),
        ]);

        this.paypalV5 = paypalV5;
    }

    protected async setup(): Promise<void> {
        const eligibleMethods = await this.findEligibleMethods();

        const unavailable = Object.entries(this.fundingSources)
            .filter(([, source]) => {
                return source === 'sepa'
                    // only consider sepa unavailable if explicitly returned
                    ? this.paypalV5?.isFundingEligible?.('sepa') === false
                    : !eligibleMethods.isEligible(source);
            })
            .map(([key]) => key)
            .sort();

        if (unavailable.join(',') === this.options.filteredPaymentMethods.sort().join(',')) {
            return;
        }

        const response = await fetch(this.options.methodEligibilityUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ paymentMethods: unavailable }),
        });

        if (!response.ok) {
            throw await PayPalPluginError.api('method-eligibility', response);
        }
    }
}
