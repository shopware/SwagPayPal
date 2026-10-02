import SwagPaypalCheckout from '../base/swag-paypal.checkout';
import PayPalPluginError from '../base/paypal-plugin.error';

export default class SwagPaypalCheckoutPayLater extends SwagPaypalCheckout<'paylater'> {
    protected get metadata(): { components: 'paypal-payments'[]; fundingSource: 'paylater'; product: 'spb' } {
        return {
            components: ['paypal-payments'],
            fundingSource: 'paylater',
            product: 'spb',
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

        if (paymentSession.hasReturned?.() === true && paymentSession.resume) {
            await this.resumeAppSwitch({ paymentSession });

            return;
        }

        this.el!.addEventListener('click', () => void this.submissionFlow({ paymentSession }));
    }

    protected async submit(data: { paymentSession: PayPalCoreJS.PaymentSession<'paylater'> }): Promise<void> {
        await this.tryStartWithAppSwitch(data);
    }
}
