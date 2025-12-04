import SwagPaypalCheckout, { type SwagPaypalCheckoutOptions } from '../base/swag-paypal.checkout';
import PayPalPluginError from '../base/paypal-plugin.error';
import '@google-pay/button-element';
import type GooglePayButton from '@google-pay/button-element';
import PayPalLoader from '../helper/paypal-loader.helper';

export interface SwagPaypalCheckoutGooglePayOptions extends SwagPaypalCheckoutOptions {
    totalPrice: string;
    currency: string;
    brandName: string;
    displayItems: google.payments.api.DisplayItem[];
}

export default class SwagPaypalCheckoutPaypal extends SwagPaypalCheckout<'googlepay'> {
    declare options: SwagPaypalCheckoutGooglePayOptions;
    declare el: GooglePayButton | undefined;

    static options: SwagPaypalCheckoutGooglePayOptions = {
        ...SwagPaypalCheckout.options,
        totalPrice: '0.00',
        currency: 'EUR',
        brandName: '',
        displayItems: [],
    };

    protected get metadata(): { components: 'googlepay-payments'[]; fundingSource: 'googlepay'; product: 'googlepay' } {
        return {
            components: ['googlepay-payments'],
            fundingSource: 'googlepay',
            product: 'googlepay',
        };
    }

    protected async beforeSetup(): Promise<void> {
        await Promise.all([
            super.beforeSetup(),
            PayPalLoader.loadGooglePay(),
        ]);
    }

    protected async setup(): Promise<void> {
        const paymentSession = this.instance!.createGooglePayOneTimePaymentSession();

        const {
            apiVersion,
            apiVersionMinor,
            allowedPaymentMethods,
            merchantInfo,
            countryCode,
            isEligible,
        } = await paymentSession.getGooglePayConfig();

        if (!isEligible) {
            throw PayPalPluginError.notEligible(this.metadata.fundingSource);
        }

        this.el!.buttonType = 'checkout';
        this.el!.environment = this.options.environment === 'sandbox' ? 'TEST' : 'PRODUCTION';
        this.el!.onPaymentAuthorized = this.onPaymentAuthorized.bind(this, paymentSession);
        this.el!.addEventListener('error', (event) => void this.onError(event.error));
        // Quote Docs: "If the browser supports Google Pay, isReadyToPay returns true"
        this.el!.addEventListener('readytopaychange', (event) => {
            if (!event.detail) {
                this.onError(PayPalPluginError.browserUnsupported(this.metadata.fundingSource));
            }
        });

        this.el!.addEventListener('click', (event) => {
            try {
                this.submitValidation({ paymentSession });
            } catch {
                event.preventDefault();
                event.stopPropagation();
            }
        }, true);


        this.el!.paymentRequest = {
            apiVersion,
            apiVersionMinor,
            allowedPaymentMethods,
            merchantInfo: {
                ...merchantInfo,
                merchantName: this.options.brandName,
            },
            callbackIntents: ['PAYMENT_AUTHORIZATION'],
            transactionInfo: {
                countryCode,
                totalPriceStatus: 'ESTIMATED', // 'FINAL',
                totalPriceLabel: 'Grand Total',
                currencyCode: this.options.currency,
                totalPrice: this.options.totalPrice,
                displayItems: Object.values(this.options.displayItems),
            },
        } satisfies google.payments.api.PaymentDataRequest;
    }

    protected afterSetup(): Promise<void> {
        this.el!.buttonRadius = Number(window.getComputedStyle(this.el!).getPropertyValue('--google-pay-button-border-radius'));

        return super.afterSetup();
    }

    protected async onPaymentAuthorized(session: PayPalCoreJS.PaymentSession<'googlepay'>, paymentData: google.payments.api.PaymentData): Promise<google.payments.api.PaymentAuthorizationResult> {
        try {
            const { orderId } = await this.createOrder();

            const confirmOrderResponse = await session.confirmOrder({
                orderId,
                paymentMethodData: paymentData.paymentMethodData,
            });

            if (!['APPROVED','PAYER_ACTION_REQUIRED'].includes(confirmOrderResponse.status)) {
                throw new Error('PayPal didn\'t approve the transaction.');
            }

            if ('PAYER_ACTION_REQUIRED' === confirmOrderResponse.status) {
                await session.initiatePayerAction({ orderId });
            }

            await this.onApprove({ orderId });

            return { transactionState: 'SUCCESS' };
        } catch (error: any) {
            this.onError(error);

            return {
                transactionState: 'ERROR',
                error: {
                    intent: 'PAYMENT_AUTHORIZATION',
                    message: error?.message as string || 'TRANSACTION FAILED',
                    reason: 'OTHER_ERROR',
                },
            };
        }
    }
}
