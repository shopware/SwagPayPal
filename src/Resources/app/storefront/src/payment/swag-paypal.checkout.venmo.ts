import SwagPaypalCheckout from '../base/swag-paypal.checkout';

export default class SwagPaypalCheckoutVenmo extends SwagPaypalCheckout<'venmo'> {
    declare el: PayPalCoreJS.HTMLVenmoButton | undefined;

    protected get metadata(): { components: 'venmo-payments'[]; fundingSource: 'venmo'; product: 'venmo' } {
        return {
            components: ['venmo-payments'],
            fundingSource: 'venmo',
            product: 'venmo',
        };
    }

    protected setup(): void {
        const paymentSession = this.instance!.createVenmoOneTimePaymentSession({
            onApprove: this.onApprove.bind(this),
            onCancel: this.onCancel.bind(this),
            onError: this.onError.bind(this),
        });

        this.el!.type = 'checkout';
        this.el!.addEventListener('click', () => void this.submissionFlow({ paymentSession }));
    }

    protected async submit(data: { paymentSession: PayPalCoreJS.PaymentSession<'venmo'> }): Promise<void> {
        await data.paymentSession.start({ presentationMode: 'auto' }, this.createOrder());
    }
}
