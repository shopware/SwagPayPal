import SwagPaypalCheckout from '../base/swag-paypal.checkout';
import PayPalPluginError from '../base/paypal-plugin.error';
import { SubmissionData } from '../base/swag-paypal.payment';

interface GooglePaySubmissionData extends SubmissionData<'googlepay'> {
    paymentSession: PayPalCoreJS.PaymentSession<'googlepay'>;
    gpClient: google.payments.api.PaymentsClient;
    paymentDataRequest: google.payments.api.PaymentDataRequest;
}

export default class SwagPaypalCheckoutPaypal extends SwagPaypalCheckout<'googlepay'> {
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

        const gpClient = this.createGPClient(paymentSession);
        const { result } = await gpClient.isReadyToPay({ apiVersion, apiVersionMinor, allowedPaymentMethods });

        // Quote Docs: "If the browser supports Google Pay, isReadyToPay returns true"
        if (!result) {
            throw PayPalPluginError.browserUnsupported(this.fundingSource);
        }

        const paymentDataRequest = {
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

        gpClient.prefetchPaymentData(paymentDataRequest);

        const button = gpClient.createButton({
            allowedPaymentMethods,
            onClick: this.submissionFlow.bind(this, { paymentSession, gpClient, paymentDataRequest }),
            buttonType: 'checkout',
            buttonSizeMode: 'fill',
            buttonRadius: this.options.buttonShape === 'pill' ? 500
                : this.options.buttonShape === 'rect' ? 4
                : 0,
        });

        button.addEventListener('click', (event) => {
            try {
                this.beforeSubmit({ paymentSession })
            } catch {
                event.preventDefault();
                event.stopPropagation();
            }
        }, true);

        this.el!.appendChild(button);
    }

    protected async submit(data: GooglePaySubmissionData): Promise<void> {
        try {
            await data.gpClient.loadPaymentData(data.paymentDataRequest)
        } catch (error: any) {
            if (error.statusCode === 'CANCELED') {
                throw PayPalPluginError.userCancelled();
            }

            throw PayPalPluginError.scriptError(error);
        }
    }

    async onPaymentAuthorized(session: PayPalCoreJS.PaymentSession<'googlepay'>, paymentData: google.payments.api.PaymentData): Promise<void> {
        const { orderId } = await this.createOrder().catch((e) => {
            this.onError(e);
            throw e;
        });

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

        this.onApprove({ orderId });
    }

    protected createGPClient(session: PayPalCoreJS.PaymentSession<'googlepay'>): google.payments.api.PaymentsClient {
        return new window.google.payments.api.PaymentsClient({
            environment: this.options.environment === 'sandbox' ? 'TEST' : 'PRODUCTION',
            paymentDataCallbacks: {
                onPaymentAuthorized: (paymentData) => this.onPaymentAuthorized(session, paymentData)
                    .then(() => ({ transactionState: 'SUCCESS' } satisfies google.payments.api.PaymentAuthorizationResult))
                    .catch((e) => ({
                        transactionState: 'ERROR',
                        error: {
                            intent: 'PAYMENT_AUTHORIZATION',
                            message: e.message || 'TRANSACTION FAILED',
                            reason: 'OTHER_ERROR'
                        },
                    })),
            },
        });
    }
}
