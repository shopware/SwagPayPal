import DomAccess from 'src/helper/dom-access.helper';
import FormSerializeUtil from 'src/utility/form/form-serialize.util';
import PageLoadingIndicatorUtil from 'src/utility/loading-indicator/page-loading-indicator.util';
import ButtonLoadingIndicator from 'src/utility/loading-indicator/button-loading-indicator.util';
import SwagPaypalAbstractStandalone from './swag-paypal.abstract-standalone';
import SwagPayPalScriptLoading from '../swag-paypal.script-loading';
import ElementLoadingIndicatorUtil from "src/utility/loading-indicator/element-loading-indicator.util";

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

        billingAddress: undefined,

        shippingAddressId: undefined,

        billingAddressId: undefined,

        modifyAddressUrl: '',

        customerEmail: '',
    };

    async render(paypal) {
        this.cardFieldForm = DomAccess.querySelector(document, this.options.cardFieldFormSelector);

        this.cardFieldForm.classList.add('py-4', 'ml-4');
        ElementLoadingIndicatorUtil.create(this.cardFieldForm.querySelector('#swag-paypal-acdc-fastlane-data'));
        const fastlane = await paypal.Fastlane({});
        fastlane.setLocale("en_us");

        const cardComponent = await fastlane.FastlaneCardComponent({});
        console.log(this.options);
        const { customerContextId } = await fastlane.identity.lookupCustomerByEmail(this.options.customerEmail);
        if (!customerContextId) {
            this.renderGuestFields(cardComponent);
            return;
        }
        const authenticationResult = await fastlane.identity.triggerAuthenticationFlow(customerContextId);
        console.log(authenticationResult);
        if (authenticationResult.authenticationState !== "succeeded") {
            this.renderGuestFields(cardComponent);
            return;
        }

        console.log("Authentication succeeded.");

        const watermarkComponent = await fastlane.FastlaneWatermarkComponent();
        watermarkComponent.render('.swag-paypal-acdc-fastlane-watermark');

        this.cardFieldForm.classList.remove('py-4', 'ml-4');
        const brand = authenticationResult.profileData.card.paymentSource.card.brand;
        const lastDigits = authenticationResult.profileData.card.paymentSource.card.lastDigits;
        this.cardFieldForm.querySelector('#swag-paypal-acdc-fastlane-data').innerHTML = `${brand} ****${lastDigits}`;

        this.cardFieldForm.querySelector('#swag-paypal-acdc-fastlane-change').classList.remove('d-none');
        this.cardFieldForm.querySelector('#swag-paypal-acdc-fastlane-change').addEventListener('click', async () => {
            const { selectedCard, selectionChanged } = await fastlane.profile.showCardSelector();
            if (!selectionChanged) {
                return;
            }

            const brand = selectedCard.paymentSource.card.brand;
            const lastDigits = selectedCard.paymentSource.card.lastDigits;
            this.cardFieldForm.querySelector('#swag-paypal-acdc-fastlane-data').innerHTML = `${brand} ****${lastDigits}`;

            console.log(selectedCard);
            this.updateAddress(this.options.billingAddressId, selectedCard.paymentSource.card.billingAddress, selectedCard.paymentSource.card.name);
        });

        document.querySelector('#swag-paypal-acdc-fastlane-shipping').addEventListener('click', async () => {
            const { selectedAddress, selectionChanged } = await fastlane.profile.showShippingAddressSelector();
            if (!selectionChanged) {
                return;
            }

            console.log(selectedAddress);
            this.updateAddress(this.options.shippingAddressId, selectedAddress.address, selectedAddress.name, selectedAddress.phoneNumber);
        });

        DomAccess.querySelector(this.confirmOrderForm, this.options.confirmOrderButtonSelector).classList.remove('d-none');
        this.confirmOrderForm.addEventListener('submit', this.onFastlaneSubmit.bind(this, fastlane, cardComponent));

        return;
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

    renderGuestFields(component) {
        this.cardFieldForm.classList.remove('py-4', 'ml-4');
        component.render(this.options.cardFieldFormSelector);
        DomAccess.querySelector(this.confirmOrderForm, this.options.confirmOrderButtonSelector).classList.remove('d-none');
    }

    updateAddress(id, address, name, phoneNumber = null) {
        this._client.post(
            this.options.modifyAddressUrl,
            JSON.stringify({
                address,
                name,
                phoneNumber,
                id,
            }),
            (response, request) => {
                if (request.status < 400) {
                    window.location.reload();
                }

                return this.onError();
            }
        );
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

    bindFieldActions(cardFields) {
        DomAccess.querySelector(this.confirmOrderForm, this.options.confirmOrderButtonSelector).classList.remove('d-none');
        this.confirmOrderForm.addEventListener('submit', this.onFieldSubmit.bind(this, cardFields));

        // remove history listener, it messes up errors
        window.PluginManager.getPluginInstanceFromElement(this.confirmOrderForm, 'FormAddHistory').options.entries = [];
    }

    async onFastlaneSubmit(fastlane, component, event) {
        const formData = FormSerializeUtil.serialize(this.confirmOrderForm);
        if (formData.has('fastlaneToken')) {
            // card fields have been successfully submitted, do regular submit
            return;
        }
        if (!this.confirmOrderForm.checkValidity()) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        PageLoadingIndicatorUtil.create();

        try {
            console.log(this.options.billingAddress);
            const token = await component.getPaymentToken({ billingAddress: this.options.billingAddress });

            const input = document.createElement('input');
            input.setAttribute('type', 'hidden');
            input.setAttribute('name', 'fastlaneToken');
            input.setAttribute('value', token.id);

            this.confirmOrderForm.appendChild(input);
            this.confirmOrderForm.submit();
        } catch (e) {
            PageLoadingIndicatorUtil.remove();
            console.error(e);

            const buttonLoadingIndicator = new ButtonLoadingIndicator(
                DomAccess.querySelector(this.confirmOrderForm, this.options.confirmOrderButtonSelector),
            );
            buttonLoadingIndicator.remove();
        }
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
