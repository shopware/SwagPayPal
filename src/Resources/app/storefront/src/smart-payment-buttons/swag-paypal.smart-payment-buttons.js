/* eslint-disable import/no-unresolved */

import DomAccess from 'src/helper/dom-access.helper';
import FormSerializeUtil from 'src/utility/form/form-serialize.util';
import StoreApiClient from 'src/service/store-api-client.service';
import PageLoadingIndicatorUtil from 'src/utility/loading-indicator/page-loading-indicator.util';
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
        commit: true,

        /**
         * This option toggles if credit card and ELV should be shown
         *
         * @type boolean
         */
        useAlternativePaymentMethods: true,

        /**
         * This option specifies if selected APMs should be hidden
         *
         * @type string[]
         */
        disabledAlternativePaymentMethods: [],

        /**
         * This option toggles if the pay later button should be shown
         *
         * @type boolean
         */
        showPayLater: true,

        /**
         * URL to create a new PayPal order
         *
         * @type string
         */
        createOrderUrl: '',

        /**
         * URL to the checkout confirm page
         *
         * @deprecated tag:v6.0.0 - will be removed
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
         * @deprecated tag:v6.0.0 - will be removed
         *
         * @type string|null
         */
        accountOrderEditUrl: '',

        /**
         * URL to the after order edit page, as the payment has failed
         *
         * @type string|null
         */
        accountOrderEditFailedUrl: '',

        /**
         * URL to the after order edit page, as the user has cancelled
         *
         * @type string|null
         */
        accountOrderEditCancelledUrl: '',

        /**
         * Selector of the selected payment method
         *
         * @deprecated tag:v6.0.0 - will be removed without replacement
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
        confirmOrderButtonSelector: 'button[type="submit"]',

        /**
         * URL for adding flash error message
         *
         * @type string
         */
        addErrorUrl: '',
    };

    init() {
        this._client = new StoreApiClient();

        this.createButton();
    }

    createButton() {
        this.createScript((paypal) => {
            this.renderButton(paypal);
        });
    }

    renderButton(paypal) {
        this.confirmOrderForm = DomAccess.querySelector(document, this.options.confirmOrderFormSelector);

        DomAccess.querySelector(this.confirmOrderForm, this.options.confirmOrderButtonSelector).classList.add('d-none');

        return paypal.Buttons(this.getButtonConfig()).render(this.el);
    }

    getButtonConfig() {
        return {
            style: {
                size: this.options.buttonSize,
                shape: this.options.buttonShape,
                color: this.options.buttonColor,
                label: 'checkout',
            },

            /**
             * Will be called if when the payment process starts
             */
            createOrder: this.createOrder.bind(this),

            /**
             * Will be called if the payment process is approved by paypal
             */
            onApprove: this.onApprove.bind(this),

            /**
             * Remove loading spinner when user comes back
             */
            onCancel: this.onCancel.bind(this),

            /**
             * Check form validity & show loading spinner on confirm click
             */
            onClick: this.onClick.bind(this),

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
        if (!this.confirmOrderForm.checkValidity()) {
            throw new Error('Checkout form not valid');
        }

        const formData = FormSerializeUtil.serialize(this.confirmOrderForm);
        formData.set('product', 'spb');
        const orderId = this.options.orderId;
        if (orderId !== null) {
            formData.set('orderId', orderId);
        }

        return new Promise((resolve, reject) => {
            this._client.post(
                this.options.createOrderUrl,
                formData,
                (responseText, request) => {
                    if (request.status >= 400) {
                        reject(responseText);
                    }

                    try {
                        const response = JSON.parse(responseText);
                        resolve(response.token);
                    } catch (error) {
                        reject(error);
                    }
                },
            );
        });
    }

    onApprove(data) {
        PageLoadingIndicatorUtil.create();

        const input = document.createElement('input');
        input.setAttribute('type', 'hidden');
        input.setAttribute('name', 'paypalOrderId');
        input.setAttribute('value', data.orderID);

        this.confirmOrderForm.appendChild(input);
        this.confirmOrderForm.submit();
    }

    onCancel() {
        this.createError(null, true);
    }

    onClick(data, actions) {
        if (!this.confirmOrderForm.checkValidity()) {
            return actions.reject();
        }

        return actions.resolve();
    }

    onError(error) {
        this.createError(error);
    }
}
