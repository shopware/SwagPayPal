import SwagPaypalCheckout from '../base/swag-paypal.checkout';

export default class SwagPaypalCheckoutVenmo extends SwagPaypalCheckout<'venmo'> {
    protected get product(): Products {
        return 'venmo' as const;
    }

    protected get fundingSource(): 'venmo' {
        return 'venmo';
    }

    protected async prepare(): Promise<void> {
        const paymentSession = this.instance.createVenmoOneTimePaymentSession({
            onApprove: this.onApprove.bind(this),
            onCancel: this.onCancel.bind(this),
            onError: this.onError.bind(this),
        });

        this.el!.addEventListener('click', () => this.submissionFlow({ paymentSession }));
    }

    protected async submit(data: { paymentSession: PayPalCoreJS.PaymentSession<'paypal'> }): Promise<void> {
        await data.paymentSession.start({ presentationMode: 'auto' }, this.createOrder());
    }
}
