/* eslint-disable import/no-unresolved */

import HttpClient from 'src/script/service/http-client.service';
import DomAccess from 'src/script/helper/dom-access.helper';
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
         * URL to the checkout confirm page
         *
         * @type string
         */
        checkoutConfirmUrl: ''
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
            const checked = DomAccess.querySelector(document, 'input.payment-method-input[checked=checked]');

            if (checked.value === this.options.paymentMethodId) {
                DomAccess.querySelector(document, '#confirmFormSubmit').style.display = 'none';
                this.el.style.display = 'block';
            } else {
                DomAccess.querySelector(document, '#confirmFormSubmit').style.display = 'block';
                this.el.style.display = 'none';
            }
        };

        toggleButtons();

        const targetNode = DomAccess.querySelector(document, '.confirm-payment');
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
            onApprove: this.onApprove.bind(this)
        };
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

    onApprove(data, actions) {
        const params = new URLSearchParams();
        params.append('paypalPayerId', data.payerID);
        params.append('paypalPaymentId', data.paymentID);

        const redirectUrl = `${this.options.checkoutConfirmUrl}?${params.toString()}`;

        actions.redirect(redirectUrl);
    }

    getScriptUrlOptions() {
        let config = super.getScriptUrlOptions();
        if (!this.options.useAlternativePaymentMethods) {
            config += '&disable-funding=card,credit,sepa';
        }

        return config;
    }
}
