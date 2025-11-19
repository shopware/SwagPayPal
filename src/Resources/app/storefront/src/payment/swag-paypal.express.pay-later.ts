import SwagPaypalExpress from '../base/swag-paypal.express';

export default class SwagPaypalExpressPayLater extends SwagPaypalExpress<'paylater'> {
    protected get product(): Products {
        return 'default' as const;
    }

    protected get fundingSource(): 'paylater' {
        return 'paylater';
    }

    protected async prepare(): Promise<void> {
        const details = await this.getFundingDetails();
        this.el!.setAttribute("productCode", details.productCode);
        this.el!.setAttribute("countryCode", details.countryCode);

        const paymentSession = this.instance.createPayLaterOneTimePaymentSession({
            onApprove: this.onApprove.bind(this),
            onCancel: this.onCancel.bind(this),
            onError: this.onError.bind(this),
        });

        this.el!.addEventListener('click', () => this.submissionFlow({ paymentSession }));
    }

    protected async submit(data: { paymentSession: PayPalCoreJS.PaymentSession<'paylater'> }): Promise<void> {
        await data.paymentSession.start({ presentationMode: 'auto' }, this.createOrder());
    }
}
