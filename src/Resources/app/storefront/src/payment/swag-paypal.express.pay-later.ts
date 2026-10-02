import SwagPaypalExpress from '../base/swag-paypal.express';

export default class SwagPaypalExpressPayLater extends SwagPaypalExpress<'paylater'> {
    protected get metadata(): { components: 'paypal-payments'[]; fundingSource: 'paylater' } {
        return {
            components: ['paypal-payments'],
            fundingSource: 'paylater',
        };
    }

    protected async setup(): Promise<void> {
        const details = await this.getFundingDetails();
        this.el!.setAttribute("productCode", details.productCode);
        this.el!.setAttribute("countryCode", details.countryCode);

        const paymentSession = this.instance!.createPayLaterOneTimePaymentSession({
            onApprove: this.onApprove.bind(this),
            onCancel: this.onCancel.bind(this),
            onError: this.onError.bind(this),
        });

        this.el!.addEventListener('click', () => void this.submissionFlow({ paymentSession }));
    }

    protected async submit(data: { paymentSession: PayPalCoreJS.PaymentSession<'paylater'> }): Promise<void> {
        await data.paymentSession.start({ presentationMode: 'auto' }, this.createOrder());
    }
}
