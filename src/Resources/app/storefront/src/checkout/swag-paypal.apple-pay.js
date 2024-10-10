import SwagPaypalAbstractStandalone from './swag-paypal.abstract-standalone';

export default class SwagPaypalApplePay extends SwagPaypalAbstractStandalone {
    static options = {
        ...super.options,

        /**
         * @type string
         */
        totalPrice: undefined,

        /**
         * @type string
         */
        brandName: undefined,

        /**
         * @type array
         */
        billingAddress: undefined,
    };

    async render(paypal) {
        if (!window.ApplePaySession?.supportsVersion(4) || !window.ApplePaySession?.canMakePayments()) {
            this.handleError(this.BROWSER_UNSUPPORTED, true, 'Browser does not support Apple Pay');
            return;
        }

        this.renderButton(paypal).catch(this.onFatalError.bind(this));
    }

    async renderButton(paypal) {
        const config = await paypal.Applepay().config();

        const button = document.createElement('apple-pay-button');
        button.setAttribute('buttonStyle', 'black');
        button.setAttribute('type', 'buy');
        button.style.width = '100%';
        button.addEventListener('click',() => {
            if (this.confirmOrderForm.checkValidity()){
                this.handleApplePayButtonSubmit(config, paypal)
                    .catch(this.onError.bind(this));
            }
        });

        if (!config.isEligible) {
            return void this.handleError(this.NOT_ELIGIBLE, true, 'Funding for Apple Pay is not eligible');
        }

        this.el.appendChild(button);
    }

    async handleApplePayButtonSubmit(config, paypal) {
        const { countryCode, merchantCapabilities, supportedNetworks, currencyCode } = config;

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

        const session = new window.ApplePaySession(4, paymentRequest);

        session.onvalidatemerchant = this.handleValidateMerchant.bind(this, session, paypal);
        session.onpaymentauthorized = this.handlePaymentAuthorized.bind(this, session, paypal);
        session.oncancel = this.onCancel.bind(this);

        session.begin();
    }

    async handleValidateMerchant(session, paypal, event) {
        try {
            const { merchantSession } = await paypal.Applepay().validateMerchant({
                validationUrl: event.validationURL,
            });

            session.completeMerchantValidation(merchantSession);
        } catch (e) {
            this.onError(e);
            session.abort();
        }
    }

    async handlePaymentAuthorized(session, paypal, event) {
        try {
            const orderId = await this.createOrder('applepay');

            await paypal.Applepay().confirmOrder({
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
