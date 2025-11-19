import SwagPaypalCheckout from '../base/swag-paypal.checkout';
import PayPalPluginError from '../base/paypal-plugin.error';
import { SubmissionData } from '../base/swag-paypal.payment';

interface ApplePaySubmissionData extends SubmissionData<'applepay'> {
    paymentSession: PayPalCoreJS.ApplePay.PaymentSession;
    config: PayPalCoreJS.ApplePay.Config
    session: ApplePaySession;
}

export default class SwagPaypalCheckoutPaypal extends SwagPaypalCheckout<'applepay'> {
    protected get product(): Products {
        return 'applepay' as const;
    }

    protected get fundingSource(): 'applepay' {
        return 'applepay';
    }

    protected async beforePrepare(): Promise<void> {
        if (!window.ApplePayMerchandising) {
            throw PayPalPluginError.scriptNotLoaded(this.fundingSource);
        }

        if (!window.ApplePaySession?.supportsVersion(4) || !window.ApplePaySession?.canMakePayments()) {
            throw PayPalPluginError.browserUnsupported(this.fundingSource);
        }

        return super.beforePrepare();
    }

    protected async prepare(): Promise<void> {
        const paymentSession = this.instance.createApplePayOneTimePaymentSession();

        const config = await paymentSession.config();

        if (!config.isEligible) {
            throw PayPalPluginError.notEligible(this.fundingSource);
        }

        this.el!.addEventListener('click', () => this.submissionFlow({ paymentSession, config }));
    }

    protected async submit(data: ApplePaySubmissionData): Promise<void> {
        const { countryCode, merchantCapabilities, supportedNetworks, currencyCode } = data.config;

        const paymentRequest = {
            countryCode,
            merchantCapabilities,
            supportedNetworks,
            currencyCode,
            requiredShippingContactFields: [],
            requiredBillingContactFields: [],
            billingContact: {
                ...this.options.billingAddress,
                addressLines: [this.options.billingAddress.addressLines],
            },
            total: {
                label: this.options.brandName,
                type: 'final',
                amount: this.options.totalPrice,
            },
        };

        data.session = new window.ApplePaySession(4, paymentRequest);

        data.session.onvalidatemerchant = this.handleValidateMerchant.bind(this, data);
        data.session.onpaymentauthorized = this.handlePaymentAuthorized.bind(this, data);
        data.session.oncancel = this.onCancel.bind(this);

        data.session.begin();
    }

    async handleValidateMerchant({ session, paymentSession }: ApplePaySubmissionData, event: ApplePayJS.ApplePayValidateMerchantEvent) {
        try {
            const { merchantSession } = await paymentSession.validateMerchant({
                validationUrl: event.validationURL,
                displayName: this.options.displayName,
            });

            session.completeMerchantValidation(merchantSession);
        } catch (e) {
            this.onError(e);
            session.abort();
        }
    }

    async handlePaymentAuthorized({ session, paymentSession }: ApplePaySubmissionData, event: ApplePayJS.ApplePayPaymentAuthorizedEvent) {
        try {
            const { orderId } = await this.createOrder();

            await paymentSession.confirmOrder({
                orderId,
                token: event.payment.token,
                billingContact: {
                    ...this.options.billingAddress,
                    addressLines: [this.options.billingAddress.addressLines],
                },
            });

            session.completePayment(window.ApplePaySession.STATUS_SUCCESS);

            this.onApprove({ orderId });
        } catch (e) {
            this.onError(e);
            session.abort();
        }
    }
}
