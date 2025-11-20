import { PaypalButtonHelper } from '../helper/paypal-button.helper';
import SwagPaypalCheckout, { type SwagPaypalCheckoutOptions } from '../base/swag-paypal.checkout';
import ElementLoadingIndicatorUtil from 'src/utility/loading-indicator/element-loading-indicator.util';

export interface SwagPaypalCheckoutAcdcOptions extends SwagPaypalCheckoutOptions {
    buttonColor: string;
    cardFieldFormSelector: string;
    cardNumberFieldSelector: string;
    cardExpiryFieldSelector: string;
    cardCvvFieldSelector: string;
    cardNameFieldSelector: string;
    fixedHeaderSelector: string;
    validatedStyleClass: string;
    cardFieldStyleConfig: Record<string, Record<string, string | number>>;
}

type Fields = Partial<Record<'form' | 'number' | 'expiry' | 'cvv' | 'name', HTMLElement | null>>;

export default class SwagPaypalCheckoutAcdc extends SwagPaypalCheckout<'advanced_cards'> {
    declare options: SwagPaypalCheckoutAcdcOptions;
    static options: SwagPaypalCheckoutAcdcOptions = {
        ...SwagPaypalCheckout.options,

        /**
         * This option specifies the PayPal button color
         */
        buttonColor: 'black',

        /**
         * Selector of the card field form
         */
        cardFieldFormSelector: '#swag-paypal-acdc-form',

        /**
         * Selector of the card number field
         */
        cardNumberFieldSelector: '#swag-paypal-acdc-form-cardnumber',

        /**
         * Selector of the expiration field
         */
        cardExpiryFieldSelector: '#swag-paypal-acdc-form-expiration',

        /**
         * Selector of the cvv field
         */
        cardCvvFieldSelector: '#swag-paypal-acdc-form-cvv',

        /**
         * Selector of the cardholder field
         */
        cardNameFieldSelector: '#swag-paypal-acdc-form-cardholder',

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
         */
        cardFieldStyleConfig: {
            input: {
                fontFamily: '"Inter", sans-serif',
                fontSize: '0.875rem',
                fontWeight: 300,
                letterSpacing: '0.03rem',
                padding: '0.5625rem',
            },
            'input::placeholder': {
                color: '#c3c3c3',
                opacity: 1,
            },
            'paypal-card-fields-network': {
                paddingLeft: 'calc(2rem + 40px) !important',
            },
        },
    };

    protected fields: Omit<Fields, 'form'> = {};

    protected orderId: string | null = null;

    protected get metadata(): { components: 'card-fields'[]; fundingSource: 'advanced_cards'; product: Products } {
        return {
            components: ['card-fields'],
            fundingSource: 'advanced_cards',
            product: 'acdc',
        };
    }

    protected get wrapperCardFields(): Fields {
        const form = document.querySelector(this.options.cardFieldFormSelector) as HTMLElement;
        return {
            form,
            number: form?.querySelector<HTMLElement>(this.options.cardNumberFieldSelector),
            expiry: form?.querySelector<HTMLElement>(this.options.cardExpiryFieldSelector),
            cvv: form?.querySelector<HTMLElement>(this.options.cardCvvFieldSelector),
            name: form?.querySelector<HTMLElement>(this.options.cardNameFieldSelector),
        };
    }

    protected async beforePrepare(): Promise<void> {
        ElementLoadingIndicatorUtil.create(this.wrapperCardFields.form!);

        return super.beforePrepare();
    }

    protected prepare(): void {
        const paymentSession = this.instance!.createCardFieldsOneTimePaymentSession();

        for (const field of ['number', 'expiry', 'cvv'] as const) {
            this.fields[field] = paymentSession.createCardFieldsComponent({
                type: field,
                placeholder: this.wrapperCardFields[field]?.dataset.placeholder || '',
                style: this.options.cardFieldStyleConfig,
            });

            this.wrapperCardFields[field]?.appendChild(this.fields[field]);
        }

        // remove history listener, it messes up errors
        const formAddHistoryPlugin = window.PluginManager.getPluginInstanceFromElement(this.confirmOrderForm, 'FormAddHistory') as SwPlugin;
        if (formAddHistoryPlugin) {
            // eslint-disable-next-line
            formAddHistoryPlugin.options.entries = [];
        }

        this.confirmOrderForm.addEventListener('submit', (event) => {
            event.preventDefault();
            event.stopPropagation();

            this.submissionFlow({ paymentSession })
                .catch(() => {
                    this.confirmOrderForm.dispatchEvent(new CustomEvent('removeLoader'));
                });
        });
    }

    protected async afterPrepare(): Promise<void> {
        ElementLoadingIndicatorUtil.remove(this.wrapperCardFields.form!);
        this.wrapperCardFields.form?.classList.remove('d-none');

        await super.afterPrepare();

        PaypalButtonHelper.enable(this.confirmOrderButton);
    }

    protected async submit(data: { paymentSession: PayPalCoreJS.PaymentSession<'advanced_cards'> }): Promise<void> {
        if ((new FormData(this.confirmOrderForm)).has('paypalOrderId')) {
            // card fields have been successfully submitted, do regular submit
            return;
        }

        this.orderId ??= (await this.createOrder()).orderId;

        try {
            await data.paymentSession.submit(this.orderId, {});
        } catch (error) {
            if (error instanceof Error && error.name === 'SdkInitError') {
                const field = error.message.match(/Invalid card data: card (\w+) field/)?.[1] as keyof Omit<Fields, 'form'> | undefined;

                if (field && ['number', 'expiry', 'cvv'].includes(field)) {
                    this.fields[field]?.focus();
                    this.wrapperCardFields[field]?.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center',
                    });

                    this.wrapperCardFields.form?.classList.add(this.options.validatedStyleClass);

                    return;
                }
            }

            throw error;
        }

        await this.onApprove({ orderId: this.orderId });
    }
}
