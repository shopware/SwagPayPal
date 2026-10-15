import SwagPaypalCheckout from '../base/swag-paypal.checkout';

export default class SwagPaypalCheckoutSepa extends SwagPaypalCheckout<'sepa'> {
    declare el: HTMLElement | undefined;

    protected get metadata(): { components: 'sepa-payments'[]; fundingSource: 'sepa'; product: 'spb' } {
        return {
            components: ['sepa-payments'],
            fundingSource: 'sepa',
            product: 'spb',
        };
    }

    /**
     * SEPA has no eligibility check in SDK v6.
     * Availability is limited to EUR and German billing addresses by the payment method's availability rule.
     */
    protected checkFundingEligiblity(): Promise<void> {
        return Promise.resolve();
    }

    protected setup(): void {
        const paymentSession = this.instance!.createSepaOneTimePaymentSession({
            onApprove: this.onApprove.bind(this),
            onCancel: this.onCancel.bind(this),
            onError: this.onError.bind(this),
            // The session needs the buyer's country, which production detects itself. Sandbox needs it set explicitly.
            testBuyerCountry: this.options.environment === 'sandbox' ? 'DE' : undefined,
        });

        this.el!.addEventListener('click', () => void this.submissionFlow({ paymentSession }));
    }

    protected async submit(data: { paymentSession: PayPalCoreJS.PaymentSession<'sepa'> }): Promise<void> {
        if (!await data.paymentSession.validate()) {
            return;
        }

        // LPM sessions only support the popup presentation mode
        await data.paymentSession.start({ presentationMode: 'popup' }, this.createOrder());
    }
}
