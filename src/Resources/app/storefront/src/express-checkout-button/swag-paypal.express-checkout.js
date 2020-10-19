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
         * URL to create a new PayPal payment
         *
         * @type string
         */
        createPaymentUrl: '',

        /**
         * URL to create a new cart in Shopware
         *
         * @type string
         */
        createNewCartUrl: '',

        /**
         * URL for the payment approval
         *
         * @type string
         */
        approvePaymentUrl: '',

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
        this._storeApiClient = new StoreApiClient();
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

        const element = DomAccess.querySelector(document, this.options.buyButtonSelector);

        return {
            element,
            disabled: element.getAttribute('disabled') === 'disabled'
        };
    }

    observeBuyButton(target, enableButton, disableButton, config = { attributes: true }) {
        const callback = (mutations) => {
            // eslint-disable-next-line no-restricted-syntax
            for (const mutation of mutations) {
                if (mutation.type !== 'attributes' || mutation.attributeName !== 'disabled') {
                    return;
                }

                const { disabled: isBuyButtonDisabled } = this.getBuyButtonState();

                if (isBuyButtonDisabled) {
                    disableButton();
                    return;
                }
                enableButton();
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
        if (this.options.addProductToCart) {
            return this.addProductToCart().then(() => {
                return this._createOrder();
            });
        }

        return this._createOrder();
    }

    /**
     * @return {Promise}
     */
    _createOrder() {
        return new Promise(resolve => {
            this._storeApiClient.get(this.options.createPaymentUrl, responseText => {
                const response = JSON.parse(responseText);
                resolve(response.token);
            });
        });
    }

    addProductToCart() {
        const buyButton = DomAccess.querySelector(this.el.closest('form'), this.options.buyButtonSelector);
        const plugin = window.PluginManager.getPluginInstanceFromElement(
            DomAccess.querySelector(document, '[data-add-to-cart]'),
            'AddToCart'
        );

        return new Promise(resolve => {
            this._storeApiClient.get(this.options.createNewCartUrl, () => {
                plugin.$emitter.subscribe('openOffCanvasCart', () => {
                    resolve();
                });

                buyButton.click();
            });
        });
    }

    onApprove(data, actions) {
        const requestPayload = {
            token: data.orderID,
            _csrf_token: DomAccess.getDataAttribute(this.el, 'swag-pay-pal-express-button-approve-payment-token')
        };

        // Add a loading indicator to the body to prevent the user breaking the checkout process
        ElementLoadingIndicatorUtil.create(document.body);

        this._httpClient.post(
            this.options.approvePaymentUrl,
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
