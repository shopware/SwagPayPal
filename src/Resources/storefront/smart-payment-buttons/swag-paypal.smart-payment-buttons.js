/* eslint-disable import/no-unresolved */

import Plugin from 'src/script/plugin-system/plugin.class';
import HttpClient from 'src/script/service/http-client.service';

export default class SwagPayPalSmartPaymentButtons extends Plugin {
    static options = {
        buttonColor: 'gold',
        buttonShape: 'rect',
        buttonSize: 'small',
        languageIso: 'en_GB',
        useSandbox: true,
        clientId: '',
        commit: false,
        tagline: false,
        useAlternativePaymentMethods: true,

        /**
         * The selector for the indicator whether the PayPal javascript is already loaded or not
         *
         * @type string
         */
        paypalScriptLoadedClass: 'paypal-checkout-js-loaded'
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

    createScript(callback = {}) {
        const scriptOptions = this.getScriptUrlOptions();
        const payPalScriptUrl = this.options.useSandbox
            ? `https://www.paypal.com/sdk/js?client-id=sb${scriptOptions}`
            : `https://www.paypal.com/sdk/js?client-id=${this.options.clientId}${scriptOptions}`;
        const payPalScript = document.createElement('script');
        payPalScript.type = 'text/javascript';
        payPalScript.src = payPalScriptUrl;

        payPalScript.addEventListener('load', callback.bind(this), false);
        document.head.appendChild(payPalScript);

        return payPalScript;
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
                label: 'pay'
            },

            onClick: this.onClick.bind(this),

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
            this._client.get('/sales-channel-api/v1/_action/paypal/spb/create-payment', responseText => {
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
            payerId: data.payerID
        };

        this._client.post(
            '/sales-channel-api/v1/_action/paypal/spb/approve-payment',
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
        let config = '';
        config += `&locale=${this.options.languageIso}`;
        config += `&commit=${this.options.commit}`;

        if (this.options.currency) {
            config += `&currency=${this.options.currency}`;
        }

        if (this.options.intent && this.options.intent !== 'sale') {
            config += `&intent=${this.options.intent}`;
        }

        if (!this.options.useAlternativePaymentMethods) {
            config += '&disable-funding=card,credit,sepa';
        }

        return config;
    }
}
