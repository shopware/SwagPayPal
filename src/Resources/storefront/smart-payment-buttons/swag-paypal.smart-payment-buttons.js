/* eslint-disable import/no-unresolved */

import HttpClient from 'src/script/service/http-client.service';
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
         * This option toggles the text below the Smart Payment buttons
         *
         * @type boolean
         */
        tagline: false,

        /**
         * This option toggles if credit card and ELV should be shown
         *
         * @type boolean
         */
        useAlternativePaymentMethods: true,

        /**
         * The selector for the indicator whether the PayPal javascript is already loaded or not
         *
         * @type string
         */
        paypalScriptLoadedClass: 'paypal-checkout-js-loaded',

        /**
         * URL to create a new PayPal payment
         *
         * @type string
         */
        createPaymentUrl: '',

        /**
         * URL for the payment approval
         *
         * @type string
         */
        approvePaymentUrl: '',
    };

    init() {
        this.paypal = null;
        this._client = new HttpClient(window.accessKey, window.contextToken);
        this.createButton();
    }

    createButton() {
        const paypalScriptLoaded = document.head.classList.contains(this.options.paypalScriptLoadedClass);

        if (paypalScriptLoaded) {
            this.paypal = window.paypal;
            this.renderButton();
            return;
        }

        this.createScript(() => {
            this.paypal = window.paypal;
            document.head.classList.add(this.options.paypalScriptLoadedClass);

            this.renderButton();
        });
    }

    renderButton() {
        const toggleButtons = () => {
            const checked = document.querySelectorAll('input.payment-method-input[checked=checked]')[0];

            if (checked.value === this.options.paymentMethodId) {
                document.getElementById('confirmFormSubmit').style.display = 'none';
                this.el.style.display = 'block';
            } else {
                document.getElementById('confirmFormSubmit').style.display = 'block';
                this.el.style.display = 'none';
            }
        };

        toggleButtons();

        const targetNode = document.querySelector('.confirm-payment');
        const config = { attributes: false, childList: true, subtree: false };
        const observer = new MutationObserver(() => {
            toggleButtons();
        });
        observer.observe(targetNode, config);

        return this.paypal.Buttons(this.getButtonConfig()).render(this.el);
    }

    getButtonConfig() {
        return {
            style: {
                layout: 'vertical',
                size: this.options.buttonSize,
                shape: this.options.buttonShape,
                color: this.options.buttonColor,
                tagline: this.options.tagline,
                label: 'pay',
            },

            onClick: this.onClick.bind(this),

            /**
             * Will be called if the express button is clicked
             */
            createOrder: this.createOrder.bind(this),

            /**
             * Will be called if the payment process is approved by paypal
             */
            onApprove: this.onApprove.bind(this),
        };
    }

    onClick(data, actions) {
        if (document.getElementById('confirmOrderForm').checkValidity()) {
            return actions.resolve();
        }
        return actions.reject();
    }

    /**
     * @return {Promise}
     */
    createOrder() {
        return new Promise(resolve => {
            this._client.post(this.options.createPaymentUrl,
                null,
                responseText => {
                    const response = JSON.parse(responseText);
                    resolve(response.token);
                });
        });
    }

    /**
     * @param data
     */
    onApprove(data) {
        const requestPayload = {
            paymentId: data.paymentID,
            payerId: data.payerID,
        };

        this._client.post(
            this.options.approvePaymentUrl,
            JSON.stringify(requestPayload),
            () => {
                if (data.payerID && data.paymentID) {
                    document.getElementById('isPayPalSpbCheckout').value = '1';
                    document.getElementById('paypalPaymentId').value = data.paymentID;
                    document.getElementById('paypalPayerId').value = data.payerID;

                    document.getElementById('confirmOrderForm').submit();
                }
            }
        );
    }

    getScriptUrlOptions() {
        let config = super.getScriptUrlOptions();
        if (!this.options.useAlternativePaymentMethods) {
            config += '&disable-funding=card,credit,sepa';
        }

        return config;
    }
}
