import template from './swag-paypal-settings-locale-select.html.twig';
import './swag-paypal-settings-locale-select.scss';
import { LOCALES, type LOCALE } from 'SwagPayPal/constant/swag-paypal-settings.constant';

type LocaleOption = {
    value: string | null;
    dashed: string | null;
    label: string;
};

export default Shopware.Component.wrapComponentConfig({
    template,

    emits: ['update:value'],

    props: {
        value: {
            type: String as PropType<LOCALE>,
            required: false,
            default: null,
        },
    },

    computed: {
        options(): LocaleOption[] {
            const options = LOCALES.map((locale) => this.toOption(locale));

            options.splice(0, 0, {
                value: null,
                dashed: null,
                label: this.$t('swag-paypal-settings-locale-select.automatic'),
            });

            if (this.invalidError) {
                options.splice(0, 0, this.toOption(this.value));
            }

            return options;
        },

        invalidError() {
            if (this.value && !LOCALES.includes(this.value)) {
                return { detail: this.$t('swag-paypal-settings-locale-select.invalid') };
            }

            return undefined;
        },
    },

    methods: {
        toOption(locale: string): LocaleOption {
            const localeDash = locale.replace('_', '-');

            return {
                value: locale,
                dashed: localeDash,
                label: this.$te(`locale.${localeDash}`) ? this.$t(`locale.${localeDash}`) : localeDash,
            };
        },

        updateValue(value: unknown) {
            this.$emit('update:value', value);
        },
    },
});
