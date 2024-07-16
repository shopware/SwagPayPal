import type * as PayPal from 'src/types';
import template from './swag-paypal-locale-field.html.twig';
import './swag-paypal-locale-field.scss';

const { debounce } = Shopware.Utils;

type ValueEvent = { target: { value?: string } };

export default Shopware.Component.wrapComponentConfig({
    template,

    inject: ['feature'],

    data(): {
        error: null | PayPal.ErrorState;
    } {
        return {
            error: null,
        };
    },

    methods: {
        onInput: debounce((event: ValueEvent) => {
            // @ts-expect-error - 'this' is not typed correctly
            // eslint-disable-next-line @typescript-eslint/no-unsafe-call
            this.checkValue(event.target.value);
        }, 350),

        onBlur(event: ValueEvent, removeFocusClass: () => void) {
            removeFocusClass();
            this.checkValue(event.target.value);
        },

        checkValue(value?: string) {
            const localeCodeRegex = /^[a-z]{2}_[A-Z]{2}$/;

            this.$emit('update:value', value || '');


            if (!value || localeCodeRegex.exec(value)) {
                this.preventSave(false);
                this.error = null;
                return;
            }

            this.preventSave(true);
            this.error = {
                code: 1,
                detail: this.$tc('swag-paypal.settingForm.locale-field.error.detail'),
            };
        },

        preventSave(mode: boolean) {
            this.$emit('preventSave', mode);
        },
    },
});
