import type * as PayPal from 'src/types';
import template from './swag-paypal-plus.html.twig';
import './swag-paypal-plus.scss';

/**
 * @deprecated tag:v10.0.0 - Will be removed without replacement.
 */
export default Shopware.Component.wrapComponentConfig({
    template,

    inject: [
        'acl',
    ],

    props: {
        actualConfigData: {
            type: Object as PropType<PayPal.SystemConfig>,
            required: true,
            default: () => { return {}; },
        },
        allConfigs: {
            type: Object as PropType<Record<string, PayPal.SystemConfig>>,
            required: true,
        },
        selectedSalesChannelId: {
            type: String,
            required: false,
            default: null,
        },
    },

    computed: {
        isPayPalPLUSActive(): boolean {
            return this.actualConfigData['SwagPayPal.settings.plusCheckoutEnabled'];
        },

        isPayPalPLUSInActive(): boolean {
            return !this.isPayPalPLUSActive;
        },
    },

    methods: {
        /**
         * @deprecated tag:v10.0.0 - Will be removed and is replaced by swag-paypal-inherit-wrapper
         */
        checkTextFieldInheritance(value: unknown): boolean {
            if (typeof value !== 'string') {
                return true;
            }

            return value.length <= 0;
        },

        /**
         * @deprecated tag:v10.0.0 - Will be removed and is replaced by swag-paypal-inherit-wrapper
         */
        checkBoolFieldInheritance(value: unknown): boolean {
            return typeof value !== 'boolean';
        },

        ifItWasNotActive(): boolean {
            return !this.actualConfigData['SwagPayPal.settings.plusCheckoutEnabled'];
        },
    },
});
