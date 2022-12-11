import DomAccess from 'src/helper/dom-access.helper';
import Iterator from 'src/helper/iterator.helper';
import FormSerializeUtil from 'src/utility/form/form-serialize.util';
import HttpClient from 'src/service/http-client.service';
import PageLoadingIndicatorUtil from 'src/utility/loading-indicator/page-loading-indicator.util';
import ButtonLoadingIndicator from 'src/utility/loading-indicator/button-loading-indicator.util';
import SwagPaypalAbstractButtons from '../swag-paypal.abstract-buttons';
import SwagPayPalScriptLoading from '../swag-paypal.script-loading';

export default class SwagPaypalAcdcFields extends SwagPaypalAbstractButtons {
    static scriptLoading = new SwagPayPalScriptLoading();

    static options = {
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
         * Is set, if the plugin is used on the order edit page
         *
         * @type string|null
         */
        orderId: null,

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
        cardFieldFormSelector: '#swag-paypal-acdc-form',

        /**
         * Selector of the card number field
         *
         * @type string
         */
        cardNumberFieldSelector: '#swag-paypal-acdc-form-cardnumber',

        /**
         * Selector of the expiration field
         *
         * @type string
         */
        expirationFieldSelector: '#swag-paypal-acdc-form-expiration',

        /**
         * Selector of the cvv field
         *
         * @type string
         */
        cvvFieldSelector: '#swag-paypal-acdc-form-cvv',

        /**
         * Selector of the cardholder field
         *
         * @type string
         */
        cardholderFieldSelector: '#swag-paypal-acdc-form-cardholder',

        /**
         * Selector of the zip field
         *
         * @type string
         */
        zipFieldSelector: '#swag-paypal-acdc-form-zip',

        /**
         * Selector of the submit button of the order confirm form
         *
         * @type string
         */
        confirmOrderButtonSelector: 'button[type="submit"]',

        /**
         * how much px the scrolling should be offset
         */
        scrollOffset: 15,

        /**
         * selector for the fixed header element
         */
        fixedHeaderSelector: 'header.fixed-top',

        /**
         * class to add when the field should have styling
         */
        validatedStyleClass: 'was-validated',

        /**
         * Styling information for the hosted fields at PayPal
         *
         * @type object
         */
        cardFieldStyleConfig: {
            input: {
                'font-family': '"Inter", sans-serif',
                'font-size': '0.875rem',
                'font-weight': 300,
                'letter-spacing': '0.02rem',
            },
            'input::placeholder': {
                color: '#c3c3c3',
                opacity: 1,
            },
        },

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
            contingencies: [
                'SCA_ALWAYS',
            ],
        },

        /**
         * If set to true, the payment method caused an error and already reloaded the page.
         * This could for example happen if the funding type is not eligible.
         *
         * @type boolean
         */
        preventErrorReload: false,
    };

    init() {
        this.confirmOrderForm = DomAccess.querySelector(document, this.options.confirmOrderFormSelector);

        if (this.options.preventErrorReload) {
            DomAccess.querySelector(this.confirmOrderForm, this.options.confirmOrderButtonSelector).disabled = 'disabled';

            return;
        }

        DomAccess.querySelector(this.confirmOrderForm, this.options.confirmOrderButtonSelector).classList.add('d-none');

        this._client = new HttpClient();

        this.createScript((paypal) => {
            this.render(paypal);
        });
    }

    render(paypal) {
        this.cardFieldForm = DomAccess.querySelector(document, this.options.cardFieldFormSelector);

        if (paypal.HostedFields.isEligible()) {
            this.cardFieldForm.classList.remove('d-none');

            paypal.HostedFields.render(this.getFieldConfig()).then(this.bindFieldActions.bind(this));
        } else {
            const button = paypal.Buttons(this.getButtonConfig(paypal.FUNDING.CARD));

            if (!button.isEligible()) {
                this.createError('Neither hosted fields nor standalone buttons are eligible');
            }

            button.render(this.el);
        }
    }

    getFieldConfig() {
        return {
            // Call your server to set up the transaction
            createOrder: this.createOrder.bind(this, 'acdc'),

            onError: this.onError.bind(this),

            styles: this.options.cardFieldStyleConfig,

            fields: {
                number: {
                    selector: this.options.cardNumberFieldSelector,
                    placeholder: DomAccess.querySelector(
                        this.cardFieldForm,
                        this.options.cardNumberFieldSelector,
                    ).dataset.placeholder,
                },
                cvv: {
                    selector: this.options.cvvFieldSelector,
                    placeholder: DomAccess.querySelector(
                        this.cardFieldForm,
                        this.options.cvvFieldSelector,
                    ).dataset.placeholder,
                },
                expirationDate: {
                    selector: this.options.expirationFieldSelector,
                    placeholder: DomAccess.querySelector(
                        this.cardFieldForm,
                        this.options.expirationFieldSelector,
                    ).dataset.placeholder,
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
        cardFields.on('validityChange', event => {
            this.setFieldValidity(event.fields[event.emittedBy]);
        });

        const regularFormFields = DomAccess.querySelectorAll(this.cardFieldForm, 'input');
        Iterator.iterate(regularFormFields, field => {
            field.addEventListener('invalid', this.onFormFieldInvalid.bind(this, cardFields));
        });

        DomAccess.querySelector(this.confirmOrderForm, this.options.confirmOrderButtonSelector).classList.remove('d-none');
        this.confirmOrderForm.addEventListener('submit', this.onFieldSubmit.bind(this, cardFields));
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

    onFieldSubmit(cardFields, event) {
        const formData = FormSerializeUtil.serialize(this.confirmOrderForm);

        if (formData.has('paypalOrderId')) {
            // card fields have been successfully submitted, do regular submit
            return;
        }

        if (!this.confirmOrderForm.checkValidity()) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        const state = cardFields.getState();
        const firstInvalidFieldKey = Object.keys(state.fields).find((key) => {
            return !state.fields[key].isValid;
        });

        if (!firstInvalidFieldKey) {
            // form and card fields filled correctly
            cardFields
                .submit(this.buildCardholderData())
                .then(this.onApprove.bind(this))
                .catch(this.onError.bind(this));

            return;
        }

        const buttonLoadingIndicator = new ButtonLoadingIndicator(
            DomAccess.querySelector(this.confirmOrderForm, this.options.confirmOrderButtonSelector),
        );
        buttonLoadingIndicator.remove();

        cardFields.focus(firstInvalidFieldKey);
        window.scrollTo({
            top: this.getScrollOffset(state.fields[firstInvalidFieldKey].container),
            behavior: 'smooth',
        });

        Object.keys(state.fields).forEach((key) => {
            this.setFieldValidity(state.fields[key], false);
        });
    }

    onApprove(data) {
        PageLoadingIndicatorUtil.create();

        const input = document.createElement('input');
        input.setAttribute('type', 'hidden');
        input.setAttribute('name', 'paypalOrderId');
        input.setAttribute('value', Object.prototype.hasOwnProperty.call(data,'orderId') ? data.orderId : data.orderID);

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

    getScrollOffset(target) {
        const rect = target.getBoundingClientRect();
        const elementScrollOffset = rect.top + window.scrollY;
        let offset = elementScrollOffset - this.options.scrollOffset;

        const fixedHeader = DomAccess.querySelector(document, this.options.fixedHeaderSelector, false);
        if (fixedHeader) {
            const headerRect = fixedHeader.getBoundingClientRect();
            offset -= headerRect.height;
        }

        return offset;
    }

    setFieldValidity(field, skipPotentialValidity = true) {
        // Remove any previously applied error or warning classes
        field.container.classList.remove('is-valid', 'is-invalid');

        if (field.isValid) {
            field.container.classList.add('is-valid');
        } else if (!field.isPotentiallyValid || !skipPotentialValidity) {
            field.container.classList.add('is-invalid');
        }
    }

    onFormFieldInvalid(cardFields) {
        this.cardFieldForm.classList.add(this.options.validatedStyleClass);

        const state = cardFields.getState();
        Object.keys(state.fields).forEach((key) => {
            this.setFieldValidity(state.fields[key], false);
        });
    }

    buildCardholderData() {
        const data = { ...this.options.cardholderData };
        const cardholderName = DomAccess.querySelector(this.cardFieldForm, this.options.cardholderFieldSelector).value;
        const zip = DomAccess.querySelector(this.cardFieldForm, this.options.zipFieldSelector).value;

        if (cardholderName) {
            data.cardholderName = cardholderName;
        }

        if (zip) {
            data.billingAddress.postalCode = zip;
        }

        return data;
    }
}
