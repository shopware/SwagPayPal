import SwagPaypalCheckout from '../base/swag-paypal.checkout';
import PayPalPluginError from '../base/paypal-plugin.error';
import '@google-pay/button-element';
import type GooglePayButton from '@google-pay/button-element';

export default class SwagPaypalCheckoutPaypal extends SwagPaypalCheckout<'googlepay'> {
    el: GooglePayButton | undefined;

    protected get product(): Products {
        return 'googlepay' as const;
    }

    protected get fundingSource(): 'googlepay' {
        return 'googlepay';
    }

    protected async beforePrepare(): Promise<void> {
        if (!window?.google?.payments?.api?.PaymentsClient) {
            throw PayPalPluginError.scriptNotLoaded(this.fundingSource);
        }

        return super.beforePrepare();
    }

    protected async prepare(): Promise<void> {
        const paymentSession = this.instance.createGooglePayOneTimePaymentSession();

        const {
            apiVersion,
            apiVersionMinor,
            allowedPaymentMethods,
            merchantInfo,
            countryCode,
            isEligible
        } = await paymentSession.getGooglePayConfig();

        if (!isEligible) {
            throw PayPalPluginError.notEligible(this.fundingSource);
        }

        this.el!.onPaymentAuthorized = this.onPaymentAuthorized.bind(this, paymentSession);
        this.el!.addEventListener('cancel', (event) => this.onCancel(event.detail));
        this.el!.addEventListener('error', (event) => this.onError(event.error));
        // Quote Docs: "If the browser supports Google Pay, isReadyToPay returns true"
        this.el!.addEventListener('readytopaychange', (event) => {
            if (!event.detail) {
                this.onError(PayPalPluginError.browserUnsupported(this.fundingSource));
            }
        });

        this.el!.addEventListener('click', (event) => {
            try {
                this.beforeSubmit({ paymentSession })
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

    protected async afterPrepare(): Promise<void> {
        this.applyStyle();

        return super.afterPrepare();
    }

    async onPaymentAuthorized(session: PayPalCoreJS.PaymentSession<'googlepay'>, paymentData: google.payments.api.PaymentData): Promise<google.payments.api.PaymentAuthorizationResult> {
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
                    message: error.message || 'TRANSACTION FAILED',
                    reason: 'OTHER_ERROR'
                },
            };
        }
    }

    protected applyStyle(): void {
        this.el!.buttonRadius = Number(window.getComputedStyle(this.el!).getPropertyValue('--google-pay-button-border-radius'));
    }
}
