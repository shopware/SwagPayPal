import type { PayPalNamespace } from '@paypal/paypal-js/types';
import PayPalPluginError from '../base/paypal-plugin.error';
import SwagPaypalBase, { type SwagPaypalBaseOptions } from '../base/swag-paypal.base';
import DependencyHelper from '../helper/dependency.helper';
import { RequestHelper } from '../helper/request.helper';

interface SwagPayPalEligibilityOptions extends SwagPaypalBaseOptions {
    filteredPaymentMethods: string[];
    methodEligibilityUrl: string;
    sepaActive: boolean;
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

        /**
         * Whether SEPA is an active payment method and therefore needs an eligibility check
         *
         * @deprecated tag:v11.0.0 - sepa implementation should be replaced by v6
         */
        sepaActive: false,
    };

    fundingSources: Record<string, PayPalCoreJS.FundingSource | 'sepa'> = {
        CARD: 'advanced_cards',
        SEPA: 'sepa',
        VENMO: 'venmo',
        PAYLATER: 'paylater',
    };

    protected get metadata(): { components: [] } {
        return {
            components: [],
        };
    }

    protected async setup(): Promise<void> {
        const [eligibleMethods, sepaEligible] = await Promise.all([
            this.findEligibleMethods(),
            this._isSepaEligible(),
        ]);

        const unavailable = Object.entries(this.fundingSources)
            .filter(([, source]) => {
                return source === 'sepa'
                    // only consider sepa unavailable if explicitly returned
                    ? sepaEligible === false
                    : !eligibleMethods.isEligible(source);
            })
            .map(([key]) => key)
            .sort();

        if (unavailable.join(',') === this.options.filteredPaymentMethods.sort().join(',')) {
            return;
        }

        const response = await RequestHelper.fetch(this.options.methodEligibilityUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ paymentMethods: unavailable }),
        });

        if (!response.ok) {
            throw await PayPalPluginError.api('method-eligibility', response);
        }
    }

    /**
     * @returns `undefined` when eligibility could not be determined
     *
     * @deprecated tag:v11.0.0 - sepa implementation should be replaced by v6
     */
    private async _isSepaEligible(): Promise<boolean | undefined> {
        if (!this.options.sepaActive) {
            return undefined;
        }

        const paypalV5: PayPalNamespace | null = await DependencyHelper.loadPayPalV5ForEligibility({
            clientId: this.options.clientId!,
            merchantId: this.options.merchantPayerId!,
            dataPartnerAttributionId: this.options.partnerAttributionId,
            locale: this.options.languageIso,
            currency: this.options.currency,
        }).catch(() => null);

        return paypalV5?.isFundingEligible?.('sepa');
    }
}
