import DomAccess from 'src/helper/dom-access.helper';
import FormSerializeUtil from 'src/utility/form/form-serialize.util';
import PageLoadingIndicatorUtil from 'src/utility/loading-indicator/page-loading-indicator.util';
import ButtonLoadingIndicator from 'src/utility/loading-indicator/button-loading-indicator.util';
import SwagPaypalAbstractStandalone from './swag-paypal.abstract-standalone';
import SwagPayPalScriptLoading from '../swag-paypal.script-loading';

export default class SwagPaypalAcdcFields extends SwagPaypalAbstractStandalone {
    /**
     * @deprecated tag:v10.0.0 - will be removed without replacement
     */
    static scriptLoading = new SwagPayPalScriptLoading();

    static options = {
        ...super.options,

        /**
         * This option specifies the PayPal button color
         *
         * @type string
         */
        buttonColor: 'black',

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
    };

    render(paypal) {
        this.cardFieldForm = DomAccess.querySelector(document, this.options.cardFieldFormSelector);

        const cardFields = paypal.CardFields(this.getFieldConfig());

        if (cardFields.isEligible()) {
            this.cardFieldForm.classList.remove('d-none');
            this.renderIndividualFields(cardFields);
            this.bindFieldActions(cardFields);
        } else {
            const button = paypal.Buttons(this.getButtonConfig(paypal.FUNDING.CARD));

            if (!button.isEligible()) {
                return void this.handleError(this.NOT_ELIGIBLE, true, 'Neither hosted fields nor standalone buttons are eligible');
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
        };
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

    bindFieldActions(cardFields) {
        DomAccess.querySelector(this.confirmOrderForm, this.options.confirmOrderButtonSelector).classList.remove('d-none');
        this.confirmOrderForm.addEventListener('submit', this.onFieldSubmit.bind(this, cardFields));

        // remove history listener, it messes up errors
        window.PluginManager.getPluginInstanceFromElement(this.confirmOrderForm, 'FormAddHistory').options.entries = [];
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
        input.setAttribute('value', data.orderID ?? data.orderId);

        this.confirmOrderForm.appendChild(input);
        this.confirmOrderForm.submit();
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
