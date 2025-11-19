import SwagPaypalExpress from '../base/swag-paypal.express';

export default class SwagPaypalExpressPaypal extends SwagPaypalExpress<'paypal'> {
    protected get product(): Products {
        return 'default' as const;
    }

    protected get fundingSource(): 'paypal' {
        return 'paypal';
    }

    protected async prepare(): Promise<void> {
        const paymentSession = this.instance.createPayPalOneTimePaymentSession({
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
