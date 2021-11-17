/* eslint-disable import/no-unresolved */

import DomAccess from 'src/helper/dom-access.helper';
import FormSerializeUtil from 'src/utility/form/form-serialize.util';
import StoreApiClient from 'src/service/store-api-client.service';
import PageLoadingIndicatorUtil from 'src/utility/loading-indicator/page-loading-indicator.util';
import SwagPaypalAbstractButtons from '../swag-paypal.abstract-buttons';

export default class SwagPaypalAcdcFields extends SwagPaypalAbstractButtons {
    static options = {
        /**
         * This option holds the client id specified in the settings
         *
         * @type string
         */
        clientId: '',

        /**
         * This option holds the client token required for field rendering
         *
         * @type string
         */
        clientToken: '',

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
         * This option specifies the language of the PayPal button
         *
         * @type string
         */
        languageIso: 'en_GB',

        /**
         * This option specifies the PayPal button color
         *
         * @type string
         */
        buttonColor: 'black',

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
         * URL to create a new PayPal order
         *
         * @type string
         */
        createOrderUrl: '',

        /**
         * Selector of the order confirm form
         *
         * @type string
         */
        confirmOrderFormSelector: '#confirmOrderForm',

        /**
         * Selector of the card field form
         *
         * @type string
         */
        cardFieldFormSelector: '#paypal-acdc-form',

        /**
         * Selector of the submit button of the order confirm form
         *
         * @type string
         */
        confirmOrderButtonSelector: 'button[type="submit"]',

        /**
         * Cardholder Data for the hosted fields
         *
         * @type object
         */
        cardholderData: {
            cardholderName: '',
            // Billing Address
            billingAddress: {
                // Street address, line 1
                streetAddress: '',
                // Street address, line 2 (Ex: Unit, Apartment, etc.)
                extendedAddress: '',
                // State
                region: '',
                // City
                locality: '',
                // Postal Code
                postalCode: '',
                // Country Code
                countryCodeAlpha2: '',
            },
        },
    };

    init() {
        this._client = new StoreApiClient();

        this.createButton();
    }

    createButton() {
        this.createScript((paypal) => {
            this.renderFields(paypal);
        });
    }

    renderFields(paypal) {
        this.confirmOrderForm = DomAccess.querySelector(document, this.options.confirmOrderFormSelector);

        if (paypal.HostedFields.isEligible()) {
            DomAccess.querySelector(document, this.options.cardFieldFormSelector).classList.remove('d-none');

            paypal.HostedFields.render(this.getFieldConfig()).then(this.bindFieldActions.bind(this));
        } else {
            DomAccess.querySelector(this.confirmOrderForm, this.options.confirmOrderButtonSelector).classList.add('d-none');

            const button = paypal.Buttons(this.getButtonConfig(paypal.FUNDING.CARD));

            if (button.isEligible()) {
                button.render(this.el);
            }
        }
    }

    getFieldConfig() {
        return {
            // Call your server to set up the transaction
            createOrder: this.createOrder.bind(this, 'acdc'),

            styles: {
                '.valid': {
                    color: 'green',
                },
                '.invalid': {
                    color: 'red',
                },
            },

            fields: {
                number: {
                    selector: '#paypal-acdc-number',
                    placeholder: '4111 1111 1111 1111',
                },
                cvv: {
                    selector: '#paypal-acdc-cvv',
                    placeholder: '123',
                },
                expirationDate: {
                    selector: '#paypal-acdc-expiration',
                    placeholder: 'MM / YY',
                },
            },
        };
    }

    getButtonConfig(fundingSource) {
        return {
            fundingSource,

            style: {
                size: this.options.buttonSize,
                shape: this.options.buttonShape,
                color: this.options.buttonColor,
                label: 'checkout',
            },

            /**
             * Will be called if when the payment process starts
             */
            createOrder: this.createOrder.bind(this, 'spb'),

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

    bindFieldActions(cardFields) {
        this.confirmOrderForm.addEventListener('submit', (event) => {
            const formData = FormSerializeUtil.serialize(this.confirmOrderForm);

            if (formData.has('paypalOrderId')) {
                return;
            }

            event.preventDefault();

            cardFields.submit(this.options.cardholderData).then(this.onApprove.bind(this));
        });
    }

    /**
     * @param product String
     *
     * @return {Promise}
     */
    createOrder(product) {
        const formData = FormSerializeUtil.serialize(this.confirmOrderForm);
        formData.set('product', product);

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
        input.setAttribute('value', data.hasOwnProperty('orderId') ? data.orderId : data.orderID);

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
