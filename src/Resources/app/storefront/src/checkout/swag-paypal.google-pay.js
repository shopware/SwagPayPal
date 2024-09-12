import SwagPaypalAbstractStandalone from './swag-paypal.abstract-standalone';
import ElementLoadingIndicatorUtil from 'src/utility/loading-indicator/element-loading-indicator.util';

export default class SwagPaypalGooglePay extends SwagPaypalAbstractStandalone {
    static options = {
        ...super.options,

        /**
         * @type string
         */
        totalPrice: undefined,

        /**
         * @type boolean
         */
        sandbox: true,

        /**
         * @type object
         */
        displayItems: {},
    };

    init() {
        super.init();

        if (!this.options.preventErrorReload) {
            ElementLoadingIndicatorUtil.create(this.el);
        }
    }

    async render(paypal) {
        await this.renderGooglePay(paypal)
            .catch(this.onFatalError.bind(this));

        ElementLoadingIndicatorUtil.remove(this.el);
    }

    async renderGooglePay(paypal) {
        if (!window?.google?.payments?.api?.PaymentsClient) {
            throw new Error('Google Pay script wasn\'t load');
        }

        const {
            isEligible,
            apiVersion,
            apiVersionMinor,
            allowedPaymentMethods,
            merchantInfo,
            countryCode,
        } = await paypal.Googlepay().config();

        if (!isEligible) {
            return void this.handleError(this.NOT_ELIGIBLE, true, 'Funding for Google Pay is not eligible');
        }

        const gpClient = this.createGPClient(paypal);
        const { result } = await gpClient.isReadyToPay({ apiVersion, apiVersionMinor, allowedPaymentMethods });

        // Quote Docs: "If the browser supports Google Pay, isReadyToPay returns true"
        if (!result) {
            return void this.handleError(this.BROWSER_UNSUPPORTED, true, 'Browser does not support Google Pay');
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
        };

        gpClient.prefetchPaymentData(paymentDataRequest);

        const button = gpClient.createButton({
            allowedPaymentMethods,
            onClick: () => {
                if (this.confirmOrderForm.checkValidity())
                    gpClient.loadPaymentData(paymentDataRequest).catch();
            },
        });

        this.el.appendChild(button);
    }

    async onPaymentAuthorized(paypal, paymentData) {
        const orderId = await this.createOrder('googlepay').catch((e) => {
            this.onError(e);
            throw e;
        });

        const confirmOrderResponse = await paypal.Googlepay().confirmOrder({
            orderId,
            paymentMethodData: paymentData.paymentMethodData,
        });

        if (!['APPROVED','PAYER_ACTION_REQUIRED'].includes(confirmOrderResponse.status)) {
            throw new Error('PayPal didn\'t approve the transaction.');
        }

        if ('PAYER_ACTION_REQUIRED' === confirmOrderResponse.status) {
            await paypal.Googlepay().initiatePayerAction({orderId});
        }

        this.onApprove({ orderId });
    }

    createGPClient(paypal) {
        const onPaymentAuthorized = (paymentData) => {
            return this.onPaymentAuthorized(paypal, paymentData)
                .then(() => ({ transactionState: 'SUCCESS' }))
                .catch((e) => ({
                    transactionState: 'ERROR',
                    error: { intent: 'PAYMENT_AUTHORIZATION', message: e.message || 'TRANSACTION FAILED' },
                }));
        };

        return new window.google.payments.api.PaymentsClient({
            environment: this.options.sandbox ? 'TEST' : 'PRODUCTION',
            paymentDataCallbacks: { onPaymentAuthorized },
        });
    }
}
