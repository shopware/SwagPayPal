/* eslint-disable import/no-unresolved */

import StoreApiClient from 'src/service/store-api-client.service';
import DomAccess from 'src/helper/dom-access.helper';
import SwagPaypalAbstractButtons from '../swag-paypal.abstract-buttons';

export default class SwagPayPalSmartPaymentButtons extends SwagPaypalAbstractButtons {
    static options = {
        /**
         * This option specifies the PayPal button color
         *
         * @type string
         */
        buttonColor: 'gold',

        /**
         * This option specifies the PayPal button shape
         *
         * @type string
         */
        buttonShape: 'rect',

        /**
         * This option specifies the PayPal button size
         *
         * @type string
         */
        buttonSize: 'small',

        /**
         * This option specifies the language of the PayPal button
         *
         * @type string
         */
        languageIso: 'en_GB',

        /**
         * This option holds the client id specified in the settings
         *
         * @type string
         */
        clientId: '',

        /**
         * This option toggles the PayNow/Login text at PayPal
         *
         * @type boolean
         */
        commit: false,

        /**
         * This option toggles if credit card and ELV should be shown
         *
         * @type boolean
         */
        useAlternativePaymentMethods: true,

        /**
         * URL to create a new PayPal payment
         *
         * @type string
         */
        createPaymentUrl: '',

        /**
         * URL to the checkout confirm page
         *
         * @type string
         */
        checkoutConfirmUrl: '',

        /**
         * Is set, if the plugin is used on the order edit page
         *
         * @type string|null
         */
        orderId: null,

        /**
         * URL to the checkout confirm page
         *
         * @type string|null
         */
        accountOrderEditUrl: '',

        /**
         * Selector of the selected payment method
         *
         * @type string
         */
        checkedPaymentMethodSelector: 'input.payment-method-input[checked=checked]',

        /**
         * Selector of the order confirm form
         *
         * @type string
         */
        confirmOrderFormSelector: '#confirmOrderForm',

        /**
         * Selector of the submit button of the order confirm form
         *
         * @type string
         */
        confirmOrderButtonSelector: 'button[type="submit"]'
    };

    init() {
        this.paypal = null;
        this._client = new StoreApiClient();

        this.createButton();
    }

    createButton() {
        this.createScript(() => {
            this.paypal = window.paypal;
            this.renderButton();
        });
    }

    renderButton() {
        const confirmOrderForm = DomAccess.querySelector(document, this.options.confirmOrderFormSelector);

        DomAccess.querySelector(confirmOrderForm, this.options.confirmOrderButtonSelector).classList.add('d-none');

        return this.paypal.Buttons(this.getButtonConfig()).render(this.el);
    }

    getButtonConfig() {
        return {
            style: {
                size: this.options.buttonSize,
                shape: this.options.buttonShape,
                color: this.options.buttonColor,
                label: 'checkout'
            },

            /**
             * Will be called if the express button is clicked
             */
            createOrder: this.createOrder.bind(this),

            /**
             * Will be called if the payment process is approved by paypal
             */
            onApprove: this.onApprove.bind(this),

            /**
             * Will be called if an error occurs during the payment process.
             */
            onError: this.onError.bind(this)
        };
    }

    /**
     * @return {Promise}
     */
    createOrder() {
        const postData = {
            _csrf_token: DomAccess.getDataAttribute(this.el, 'swag-pay-pal-smart-payment-buttons-create-payment-token')
        };
        const orderId = this.options.orderId;
        if (orderId !== null) {
            postData.orderId = orderId;
        }

        return new Promise(resolve => {
            this._client.post(
                this.options.createPaymentUrl,
                JSON.stringify(postData),
                responseText => {
                    const response = JSON.parse(responseText);
                    resolve(response.token);
                }
            );
        });
    }

    onApprove(data, actions) {
        const params = new URLSearchParams();
        let url = this.options.checkoutConfirmUrl;
        params.append('paypalPayerId', data.payerID);
        params.append('paypalPaymentId', data.paymentID);

        if (this.options.accountOrderEditUrl !== null) {
            url = this.options.accountOrderEditUrl;
        }

        const redirectUrl = `${url}?${params.toString()}`;

        actions.redirect(redirectUrl);
    }

    onError() {
        this.createError();
    }
}
