import HttpClient from 'src/service/http-client.service';
import DomAccess from 'src/helper/dom-access.helper';
import ElementLoadingIndicatorUtil from 'src/utility/loading-indicator/element-loading-indicator.util';
import SwagPaypalAbstractButtons from '../swag-paypal.abstract-buttons';
import SwagPayPalScriptLoading from '../swag-paypal.script-loading';

export default class SwagPayPalExpressCheckoutButton extends SwagPaypalAbstractButtons {
    static scriptLoading = new SwagPayPalScriptLoading();

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
         * This option holds the merchant id specified in the settings
         *
         * @type string
         */
        merchantPayerId: '',

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
        payPalPaymentMethodId: '',

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
        addErrorUrl: '',

        /**
         * URL for redirecting to after user cancels
         *
         * @type string
         */
        cancelRedirectUrl: '',

        /**
         * Show additional pay later button
         *
         * @type boolean
         */
        disablePayLater: true,
    };

    init() {
        this._client = new HttpClient();
        this.createButton();
    }

    createButton() {
        this.createScript((paypal) => {
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
                disabled: false,
            };
        }

        const element = DomAccess.querySelector(this.el.closest('form'), this.options.buyButtonSelector);

        return {
            element,
            disabled: element.disabled,
        };
    }

    observeBuyButton(target, enableButton, disableButton, config = { attributes: true }) {
        const callback = (mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.attributeName === 'disabled') {
                    const { disabled: isBuyButtonDisabled } = this.getBuyButtonState();

                    if (isBuyButtonDisabled) {
                        disableButton();
                        return;
                    }
                    enableButton();
                }
            });
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
                height: 40,
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
             * Will be called if the user cancels the checkout.
             */
            onCancel: this.onCancel.bind(this),

            /**
             * Will be called if an error occurs during the payment process.
             */
            onError: this.onError.bind(this),
        };
    }

    /**
     * @return {Promise}
     */
    createOrder() {
        const switchPaymentMethodData = { paymentMethodId: this.options.payPalPaymentMethodId };

        return new Promise((resolve, reject) => {
            this._client.patch(
                this.options.contextSwitchUrl,
                JSON.stringify(switchPaymentMethodData),
                (responseText, request) => {
                    if (request.status >= 400) {
                        reject(responseText);
                    }

                    return Promise.resolve().then(() => {
                        if (this.options.addProductToCart) {
                            return this.addProductToCart();
                        }

                        return Promise.resolve();
                    }).then(() => {
                        return this._createOrder();
                    }).then(token => {
                        resolve(token);
                    })
                        .catch((error) => {
                            reject(error);
                        });
                },
            );
        });
    }

    /**
     * @return {Promise}
     */
    _createOrder() {
        return new Promise((resolve, reject) => {
            this._client.post(this.options.createOrderUrl, new FormData(), (responseText, request) => {
                if (request.status >= 400) {
                    reject(responseText);
                }

                try {
                    const response = JSON.parse(responseText);
                    resolve(response.token);
                } catch (error) {
                    reject(error);
                }
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
            token: data.orderID,
        };

        // Add a loading indicator to the body to prevent the user breaking the checkout process
        ElementLoadingIndicatorUtil.create(document.body);

        this._client.post(
            this.options.prepareCheckoutUrl,
            JSON.stringify(requestPayload),
            (response, request) => {
                if (request.status < 400) {
                    return actions.redirect(this.options.checkoutConfirmUrl);
                }

                return this.createError(response, false, this.options.cancelRedirectUrl);
            },
        );
    }

    onError(error) {
        this.createError(error);
    }

    onCancel(error) {
        this.createError(error, true, this.options.cancelRedirectUrl);
    }
}
