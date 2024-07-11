import template from './swag-paypal-locale-field.html.twig';
import './swag-paypal-locale-field.scss';

const { debounce } = Shopware.Utils;

export default Shopware.Component.wrapComponentConfig({
    template,

    inject: ['feature'],

    data() {
        return {
            error: null,
        };
    },

    methods: {
        onInput: debounce((event) => {
            this.checkValue(event.target.value);
        }, 350),

        onBlur(event, removeFocusClass) {
            removeFocusClass();
            this.checkValue(event.target.value);
        },

        checkValue(value) {
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

        preventSave(mode) {
            this.$emit('preventSave', mode);
        },
    },
});
