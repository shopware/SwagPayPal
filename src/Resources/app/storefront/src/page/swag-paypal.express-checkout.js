import HttpClient from 'src/service/http-client.service';
import DomAccess from 'src/helper/dom-access.helper';
import ElementLoadingIndicatorUtil from 'src/utility/loading-indicator/element-loading-indicator.util';
import SwagPaypalAbstractButtons from '../swag-paypal.abstract-buttons';
import SwagPayPalScriptLoading from '../swag-paypal.script-loading';

export default class SwagPayPalExpressCheckoutButton extends SwagPaypalAbstractButtons {
    /**
     * @deprecated tag:v10.0.0 - will be removed without replacement
     */
    static scriptLoading = new SwagPayPalScriptLoading();

    static options = {
        ...super.options,

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
        buttonShape: 'sharp',

        /**
         * This option specifies the PayPal button size
         *
         * @type string
         */
        buttonSize: 'small',

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
         * @deprecated tag:v10.0.0 - Will be removed, use {@link handleErrorUrl} instead
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
        showPayLater: true,

        /**
         * List of payment sources to be rendered
         *
         * @type string[]
         */
        fundingSources: [],

        /*
         * Streamline options for listing pages, overriding the ones
         * from swag-paypal.script-loading.js
         */
        useAlternativePaymentMethods: true,
        commit: false,
        scriptAwaitVisibility: true,
        partOfDomContentLoading: false,
    };

    GENERIC_ERROR = 'SWAG_PAYPAL__EXPRESS_GENERIC_ERROR';
    USER_CANCELLED = 'SWAG_PAYPAL__EXPRESS_USER_CANCELLED';

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
        this.options.fundingSources.forEach((fundingSource) => {
            const button = paypal.Buttons(this.getButtonConfig(fundingSource));

            if (button.isEligible()) {
                button.render(this.el);
            }
        });

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

    getButtonConfig(fundingSource = 'paypal') {
        const renderElement = this.el;
        const { element: buyButton, disabled: isBuyButtonDisabled } = this.getBuyButtonState();

        return {
            fundingSource,

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
                layout: 'vertical',
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
        const switchPaymentMethodData = {
            paymentMethodId: this.options.payPalPaymentMethodId,
            deleteCart: this.options.addProductToCart,
        };

        return new Promise((resolve, reject) => {
            this._client.post(
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
            plugin.$emitter.subscribe('openOffCanvasCart', () => {
                resolve();
            });

            buyButton.click();
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

                return this.onError();
            },
        );
    }

    onErrorHandled(code, fatal, error) {
        if (code === this.GENERIC_ERROR || code === this.USER_CANCELLED) {
            window.scrollTo(0, 0);
            window.location = this.options.cancelRedirectUrl;
        } else {
            super.onErrorHandled(code, fatal, error);
        }
    }
}
