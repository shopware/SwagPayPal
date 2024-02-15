import DomAccess from 'src/helper/dom-access.helper';
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
        cardExpiryFieldSelector: '#swag-paypal-acdc-form-expiration',

        /**
         * Selector of the cvv field
         *
         * @type string
         */
        cardCvvFieldSelector: '#swag-paypal-acdc-form-cvv',

        /**
         * Selector of the cardholder field
         *
         * @type string
         */
        cardNameFieldSelector: '#swag-paypal-acdc-form-cardholder',

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
         * Styling information for the card fields at PayPal
         *
         * @type object
         */
        cardFieldStyleConfig: {
            input: {
                'font-family': '"Inter", sans-serif',
                'font-size': '0.875rem',
                'font-weight': 300,
                'letter-spacing': '0.03rem',
                padding: '0.5625rem',
            },
            'input::placeholder': {
                color: '#c3c3c3',
                opacity: 1,
            },
            body: {
                padding: 0,
            },
            'input.card-field-number.display-icon': {
                'padding-left': 'calc(2rem + 40px) !important',
            },
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

        const cardFields = paypal.CardFields(this.getFieldConfig());

        if (cardFields.isEligible()) {
            this.cardFieldForm.classList.remove('d-none');
            this.renderIndividualFields(cardFields)
            this.bindFieldActions(cardFields);
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

            onApprove: this.onApprove.bind(this),

            style: this.options.cardFieldStyleConfig,
        }
    }

    renderIndividualFields(cardFields) {
        this.fields = {};

        this.fields.cardNameField = cardFields.NameField({
            placeholder: DomAccess.querySelector(
                this.cardFieldForm,
                this.options.cardNameFieldSelector,
            ).dataset.placeholder,
        });
        this.fields.cardNameField.render(this.options.cardNameFieldSelector);

        this.fields.cardNumberField = cardFields.NumberField({
            placeholder: DomAccess.querySelector(
                this.cardFieldForm,
                this.options.cardNumberFieldSelector,
            ).dataset.placeholder,
        });
        this.fields.cardNumberField.render(this.options.cardNumberFieldSelector);

        this.fields.cardCvvField = cardFields.CVVField({
            placeholder: DomAccess.querySelector(
                this.cardFieldForm,
                this.options.cardCvvFieldSelector,
            ).dataset.placeholder,
        });
        this.fields.cardCvvField.render(this.options.cardCvvFieldSelector);

        this.fields.cardExpiryField = cardFields.ExpiryField({
            placeholder: DomAccess.querySelector(
                this.cardFieldForm,
                this.options.cardExpiryFieldSelector,
            ).dataset.placeholder,
        });
        this.fields.cardExpiryField.render(this.options.cardExpiryFieldSelector);
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
        DomAccess.querySelector(this.confirmOrderForm, this.options.confirmOrderButtonSelector).classList.remove('d-none');
        this.confirmOrderForm.addEventListener('submit', this.onFieldSubmit.bind(this, cardFields));

        // remove history listener, it messes up errors
        window.PluginManager.getPluginInstanceFromElement(this.confirmOrderForm, 'FormAddHistory').options.entries = [];
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

        cardFields.getState().then((state) => {
            if (state.isFormValid) {
                // form and card fields filled correctly
                cardFields.submit();

                return;
            }

            const buttonLoadingIndicator = new ButtonLoadingIndicator(
                DomAccess.querySelector(this.confirmOrderForm, this.options.confirmOrderButtonSelector),
            );
            buttonLoadingIndicator.remove();

            const firstInvalidFieldKey = Object.keys(state.fields).find((key) => !state.fields[key].isValid);
            this.fields[firstInvalidFieldKey]?.focus();

            window.scrollTo({
                top: this.getScrollOffset(DomAccess.querySelector(this.cardFieldForm, this.options[firstInvalidFieldKey + 'Selector'])),
                behavior: 'smooth',
            });
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
}
