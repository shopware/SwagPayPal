import SwagPaypalCheckout from '../base/swag-paypal.checkout';

export default class SwagPaypalCheckoutPaypal extends SwagPaypalCheckout<'paypal'> {
    declare el: PayPalCoreJS.HTMLPaypalButton | undefined;

    protected get metadata(): { components: 'paypal-payments'[]; fundingSource: 'paypal'; product: 'spb' } {
        return {
            components: ['paypal-payments'],
            fundingSource: 'paypal',
            product: 'spb',
        };
    }

    protected async setup(): Promise<void> {
        const paymentSession = this.instance!.createPayPalOneTimePaymentSession({
            onApprove: this.onApprove.bind(this),
            onCancel: this.onCancel.bind(this),
            onError: this.onError.bind(this),
        });

        if (paymentSession.hasReturned?.() === true && paymentSession.resume) {
            await this.resumeAppSwitch({ paymentSession });

            return;
        }

        this.el!.type = 'checkout';
        this.el!.addEventListener('click', () => void this.submissionFlow({ paymentSession }));
    }

    protected async submit(data: { paymentSession: PayPalCoreJS.PaymentSession<'paypal'> }): Promise<void> {
        await this.tryStartWithAppSwitch(data);
    }
}
