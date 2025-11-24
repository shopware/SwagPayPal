import SwagPaypalExpress from '../base/swag-paypal.express';

export default class SwagPaypalExpressPaypal extends SwagPaypalExpress<'paypal'> {
    declare el: PayPalCoreJS.HTMLPaypalButton | undefined;

    protected get metadata(): { components: 'paypal-payments'[]; fundingSource: 'paypal'; product: 'default' } {
        return {
            components: ['paypal-payments'],
            fundingSource: 'paypal',
            product: 'default',
        };
    }


    protected setup(): void {
        const paymentSession = this.instance!.createPayPalOneTimePaymentSession({
            onApprove: this.onApprove.bind(this),
            onCancel: this.onCancel.bind(this),
            onError: this.onError.bind(this),
        });

        this.el!.type = 'buynow';
        this.el!.addEventListener('click', () => void this.submissionFlow({ paymentSession }));
    }

    protected async submit(data: { paymentSession: PayPalCoreJS.PaymentSession<'paypal'> }): Promise<void> {
        await data.paymentSession.start({ presentationMode: 'auto' }, this.createOrder());
    }
}
