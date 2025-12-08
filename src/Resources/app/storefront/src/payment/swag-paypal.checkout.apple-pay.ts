import type { SwagPaypalCheckoutOptions } from '../base/swag-paypal.checkout';
import SwagPaypalCheckout from '../base/swag-paypal.checkout';
import PayPalPluginError from '../base/paypal-plugin.error';
import type { SubmissionData } from '../base/swag-paypal.payment';
import PayPalLoader from '../helper/paypal-loader.helper';

export interface SwagPaypalCheckoutApplePayOptions extends SwagPaypalCheckoutOptions {
    totalPrice: string;
    brandName: string;
    displayName: string;
    billingAddress: ApplePayJS.ApplePayPaymentContact&{
        addressLines?: string;
    };
}

interface ApplePaySubmissionData extends SubmissionData<'applepay'> {
    paymentSession: PayPalCoreJS.ApplePay.PaymentSession;
    config: PayPalCoreJS.ApplePay.Config;
    session: ApplePaySession;
}

export default class SwagPaypalCheckoutPaypal extends SwagPaypalCheckout<'applepay'> {
    declare options: SwagPaypalCheckoutApplePayOptions;
    declare el: (HTMLElement&{ type: 'check-out' }) | undefined;

    static options: SwagPaypalCheckoutApplePayOptions = {
        ...SwagPaypalCheckout.options,
        totalPrice: '0.00',
        brandName: '',
        displayName: '',
        billingAddress: {},
    };

    protected get metadata(): { components: 'applepay-payments'[]; fundingSource: 'applepay'; product: 'applepay' } {
        return {
            components: ['applepay-payments'],
            fundingSource: 'applepay',
            product: 'applepay',
        };
    }

    protected async beforeSetup(): Promise<void> {
        await Promise.all([
            super.beforeSetup(),
            PayPalLoader.loadApplePay(),
        ]);

        if (!window.ApplePaySession?.supportsVersion(4) || !window.ApplePaySession?.canMakePayments()) {
            throw PayPalPluginError.browserUnsupported(this.metadata.fundingSource);
        }
    }

    protected async setup(): Promise<void> {
        const paymentSession = this.instance!.createApplePayOneTimePaymentSession();

        const config = await paymentSession.config();

        if (!config.isEligible) {
            throw PayPalPluginError.notEligible(this.metadata.fundingSource);
        }

        this.el!.type = 'check-out';
        this.el!.addEventListener('click', () => void this.submissionFlow({ paymentSession, config }));
    }

    protected submit(data: ApplePaySubmissionData): void {
        const paymentRequest = {
            merchantCapabilities: data.config.merchantCapabilities,
            supportedNetworks: data.config.supportedNetworks,
            countryCode: data.config.countryCode ?? data.config.merchantCountry,
            currencyCode: data.config.currencyCode ?? this.options.currency,
            requiredShippingContactFields: [],
            requiredBillingContactFields: [],
            billingContact: {
                ...this.options.billingAddress,
                addressLines: this.options.billingAddress.addressLines ? [this.options.billingAddress.addressLines] : undefined,
            },
            total: {
                label: this.options.brandName,
                type: 'final',
                amount: this.options.totalPrice,
            },
        } satisfies ApplePayJS.ApplePayPaymentRequest;

        data.session = new window.ApplePaySession!(4, paymentRequest);

        data.session.onvalidatemerchant = (event) => void this.handleValidateMerchant(data, event);
        data.session.onpaymentauthorized = (event) => void this.handlePaymentAuthorized(data, event);
        data.session.oncancel = this.onCancel.bind(this);

        data.session.begin();
    }

    protected async handleValidateMerchant({ session, paymentSession }: ApplePaySubmissionData, event: ApplePayJS.ApplePayValidateMerchantEvent): Promise<void> {
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

    protected async handlePaymentAuthorized({ session, paymentSession }: ApplePaySubmissionData, event: ApplePayJS.ApplePayPaymentAuthorizedEvent): Promise<void> {
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

            session.completePayment(window.ApplePaySession!.STATUS_SUCCESS);

            this.onApprove({ orderId });
        } catch (e) {
            this.onError(e);
            session.abort();
        }
    }
}
