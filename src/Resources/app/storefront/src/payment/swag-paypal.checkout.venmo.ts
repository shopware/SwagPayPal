import SwagPaypalCheckout from '../base/swag-paypal.checkout';

export default class SwagPaypalCheckoutVenmo extends SwagPaypalCheckout<'venmo'> {
    protected get metadata(): { components: 'venmo-payments'[]; fundingSource: 'venmo'; product: 'venmo' } {
        return {
            components: ['venmo-payments'],
            fundingSource: 'venmo',
            product: 'venmo',
        };
    }

    protected prepare(): void {
        const paymentSession = this.instance!.createVenmoOneTimePaymentSession({
            onApprove: this.onApprove.bind(this),
            onCancel: this.onCancel.bind(this),
            onError: this.onError.bind(this),
        });

        this.el!.addEventListener('click', () => void this.submissionFlow({ paymentSession }));
    }

    protected async submit(data: { paymentSession: PayPalCoreJS.PaymentSession<'paypal'> }): Promise<void> {
        await data.paymentSession.start({ presentationMode: 'auto' }, this.createOrder());
    }
}
