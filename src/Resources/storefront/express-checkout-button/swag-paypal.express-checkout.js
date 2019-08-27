/* eslint-disable import/no-unresolved */

import HttpClient from 'src/script/service/http-client.service';
import ElementLoadingIndicatorUtil from 'src/script/utility/loading-indicator/element-loading-indicator.util';
import FormSerializeUtil from 'src/script/utility/form/form-serialize.util';
import SwagPaypalAbstractButtons from '../swag-paypal.abstract-buttons';

const OFF_CANVAS_CART_CLOSE_BUTTON_SELECTOR = '.btn.btn-light.btn-block.offcanvas-close.js-offcanvas-close.sticky-top';

export default class SwagPayPalExpressCheckoutButton extends SwagPaypalAbstractButtons {
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
         * This option specifies if the PayPal button appears on the checkout/register page
         *
         * @type boolean
         */
        loginEnabled: false,

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
         * URL to add a new LineItem to the cart
         *
         * @type string
         */
        addLineItemUrl: '',

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
        checkoutConfirmUrl: ''
    };

    init() {
        this._client = new HttpClient(window.accessKey, window.contextToken);
        this.paypal = null;
        this.createButton();
    }

    createButton() {
        this.createScript(() => {
            this.paypal = window.paypal;
            this.renderButton();
        });
    }

    renderButton() {
        return this.paypal.Buttons(this.getButtonConfig()).render(this.el);
    }

    getButtonConfig() {
        return {
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
            onApprove: this.onApprove.bind(this)
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
            this._client.get(this.options.createPaymentUrl, responseText => {
                const response = JSON.parse(responseText);
                resolve(response.token);
            });
        });
    }

    addProductToCart() {
        const formattedLineItems = this._formatLineItems();

        return new Promise(resolve => {
            this._client.get(this.options.createNewCartUrl, () => {
                this._client.post(this.options.addLineItemUrl, JSON.stringify(formattedLineItems), () => {
                    resolve();
                });
            });
        });
    }

    /**
     * Returns the line item data with keys like: lineItems[06e28a73ecd44a0e84e5ddf144dff8d7][quantity],
     * as a proper Object.
     * @return {Object}
     */
    _formatLineItems() {
        const formData = FormSerializeUtil.serializeJson(this.el.closest('form'));

        const formattedLineItems = {};
        Object.keys(formData).forEach(key => {
            const matches = key.match(/lineItems\[(.+)]\[(.+)]/);

            if (key !== 'redirectTo' && matches && matches.length === 3) {
                if (!formattedLineItems[matches[1]]) {
                    formattedLineItems[matches[1]] = {
                        [matches[2]]: formData[matches[0]]
                    };
                } else {
                    const lineItem = formattedLineItems[matches[1]];

                    lineItem[matches[2]] = formData[matches[0]];
                }
            }
        });

        return {
            lineItems: formattedLineItems
        };
    }

    onApprove(data, actions) {
        const offCanvasCloseButton = document.querySelector(OFF_CANVAS_CART_CLOSE_BUTTON_SELECTOR);
        const requestPayload = { paymentId: data.paymentID };

        // If the offCanvasCartCloseButton is visible, the offCanvasCart is closed by clicking the element
        if (offCanvasCloseButton) {
            offCanvasCloseButton.click();
        }

        // Add a loading indicator to the body to prevent the user breaking the checkout process
        ElementLoadingIndicatorUtil.create(document.body);

        this._client.post(
            this.options.approvePaymentUrl,
            JSON.stringify(requestPayload),
            () => {
                actions.redirect(this.options.checkoutConfirmUrl);
            }
        );
    }
}
