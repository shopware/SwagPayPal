/* eslint-disable import/no-unresolved */

import HttpClient from 'src/service/http-client.service';
import StoreApiClient from 'src/service/store-api-client.service';
import DomAccess from 'src/helper/dom-access.helper';
import ElementLoadingIndicatorUtil from 'src/utility/loading-indicator/element-loading-indicator.util';
import SwagPaypalAbstractButtons from '../swag-paypal.abstract-buttons';

export default class SwagPayPalExpressCheckoutButton extends SwagPaypalAbstractButtons {
    static options = {

        /**
         * This option defines the class name which will be added when the button gets disabled.
         *
         * @type string
         */
        disabledClass: 'is-disabled',

        /**
         * This option defines the selector for the buy button on the product detail page and listing.
         *
         * @type string
         */
        buyButtonSelector: '.btn-buy',

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
         * This options specifies the currency of the PayPal button
         *
         * @type string
         */
        currency: 'EUR',

        /**
         * This options defines the payment intent
         *
         * @type string
         */
        intent: 'capture',

        /**
         * This option toggles the PayNow/Login text at PayPal
         *
         * @type boolean
         */
        commit: false,

        /**
         * This option toggles the text below the PayPal Express button
         *
         * @type boolean
         */
        tagline: false,

        /**
         * This option toggles the Process whether or not the product needs to be added to the cart.
         *
         * @type boolean
         */
        addProductToCart: false,

        /**
         * URL to set payment method to PayPal
         *
         * @type string
         */
        contextSwitchUrl: '',

        /**
         * @type string
         */
        payPaLPaymentMethodId: '',

        /**
         * URL to create a new PayPal order
         *
         * @type string
         */
        createOrderUrl: '',

        /**
         * URL to delete an existing cart in Shopware
         *
         * @type string
         */
        deleteCartUrl: '',

        /**
         * URL for creating and logging in guest customer
         *
         * @type string
         */
        prepareCheckoutUrl: '',

        /**
         * URL to the checkout confirm page
         *
         * @type string
         */
        checkoutConfirmUrl: '',

        /**
         * URL for adding flash error message
         *
         * @type string
         */
        addErrorUrl: ''
    };

    init() {
        this._client = new StoreApiClient();
        this._httpClient = new HttpClient();
        this.createButton();
    }

    createButton() {
        this.createScript(() => {
            const paypal = window.paypal;
            this.renderButton(paypal);
        });
    }

    renderButton(paypal) {
        return paypal.Buttons(this.getButtonConfig()).render(this.el);
    }

    getBuyButtonState() {
        if (!this.options.addProductToCart) {
            return {
                element: null,
                disabled: false
            };
        }

        const element = DomAccess.querySelector(this.el.closest('form'), this.options.buyButtonSelector);

        return {
            element,
            disabled: element.disabled
        };
    }

    observeBuyButton(target, enableButton, disableButton, config = { attributes: true }) {
        const callback = (mutations) => {
            // eslint-disable-next-line no-restricted-syntax
            for (const mutation of mutations) {
                if (mutation.attributeName === 'disabled') {
                    const { disabled: isBuyButtonDisabled } = this.getBuyButtonState();

                    if (isBuyButtonDisabled) {
                        disableButton();
                        return;
                    }
                    enableButton();
                }
            }
        };

        const observer = new MutationObserver(callback);
        observer.observe(target, config);

        return observer;
    }

    getButtonConfig() {
        const renderElement = this.el;
        const { element: buyButton, disabled: isBuyButtonDisabled } = this.getBuyButtonState();

        return {
            onInit: (data, actions) => {
                if (!this.options.addProductToCart) {
                    return;
                }

                /**
                 * Helper method which enables the paypal button
                 * @returns void
                 */
                const enableButton = () => {
                    actions.enable();
                    renderElement.classList.remove(this.options.disabledClass);
                };

                /**
                 * Helper method which disables the paypal button
                 * @returns void
                 */
                const disableButton = () => {
                    actions.disable();
                    renderElement.classList.add(this.options.disabledClass);
                };

                this.observeBuyButton(buyButton, enableButton, disableButton);

                // Set the initial state of the button
                if (isBuyButtonDisabled) {
                    disableButton();
                    return;
                }
                enableButton();
            },
            style: {
                size: this.options.buttonSize,
                shape: this.options.buttonShape,
                color: this.options.buttonColor,
                tagline: this.options.tagline,
                layout: 'horizontal',
                label: 'checkout',
                height: 40
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
        const switchPaymentMethodData = { paymentMethodId: this.options.payPaLPaymentMethodId };

        return new Promise(resolve => {
            this._client.patch(this.options.contextSwitchUrl, JSON.stringify(switchPaymentMethodData), () => {
                if (this.options.addProductToCart) {
                    return this.addProductToCart().then(() => {
                        resolve(this._createOrder());
                    });
                }

                return resolve(this._createOrder());
            });
        });
    }

    /**
     * @return {Promise}
     */
    _createOrder() {
        return new Promise(resolve => {
            this._client.post(this.options.createOrderUrl, new FormData(), responseText => {
                const response = JSON.parse(responseText);
                resolve(response.token);
            });
        });
    }

    addProductToCart() {
        const buyForm = this.el.closest('form');
        const buyButton = DomAccess.querySelector(buyForm, this.options.buyButtonSelector);
        const plugin = window.PluginManager.getPluginInstanceFromElement(buyForm, 'AddToCart');

        return new Promise(resolve => {
            this._client.delete(this.options.deleteCartUrl, null, () => {
                plugin.$emitter.subscribe('openOffCanvasCart', () => {
                    resolve();
                });

                buyButton.click();
            });
        });
    }

    onApprove(data, actions) {
        const requestPayload = {
            token: data.orderID
        };

        // Add a loading indicator to the body to prevent the user breaking the checkout process
        ElementLoadingIndicatorUtil.create(document.body);

        this._client.post(
            this.options.prepareCheckoutUrl,
            JSON.stringify(requestPayload),
            () => {
                actions.redirect(this.options.checkoutConfirmUrl);
            }
        );
    }

    onError() {
        this.createError();
    }
}
