import SwagPaypalExpress from '../base/swag-paypal.express';

export default class SwagPaypalExpressVenmo extends SwagPaypalExpress<'venmo'> {
    declare el: PayPalCoreJS.HTMLVenmoButton | undefined;

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
            onCancel: this.onCancel.bind(this),/*  */
            onError: this.onError.bind(this),
        });

        this.el!.type = 'buynow';
        this.el!.addEventListener('click', () => void this.submissionFlow({ paymentSession }));
    }

    protected async submit(data: { paymentSession: PayPalCoreJS.PaymentSession<'venmo'> }): Promise<void> {
        await data.paymentSession.start({ presentationMode: 'auto' }, this.createOrder());
    }
}
