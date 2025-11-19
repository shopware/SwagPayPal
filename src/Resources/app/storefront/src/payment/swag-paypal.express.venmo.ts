import SwagPaypalExpress from '../base/swag-paypal.express';

export default class SwagPaypalExpressVenmo extends SwagPaypalExpress<'venmo'> {
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

    protected async submit(data: { paymentSession: PayPalCoreJS.PaymentSession<'venmo'> }): Promise<void> {
        await data.paymentSession.start({ presentationMode: 'auto' }, this.createOrder());
    }
}
