import type * as PayPal from 'src/types';
import template from './swag-paypal-pui.html.twig';

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
    },
});
