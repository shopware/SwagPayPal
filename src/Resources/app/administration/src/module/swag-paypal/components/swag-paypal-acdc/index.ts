import type * as PayPal from 'src/types';
import template from './swag-paypal-acdc.html.twig';

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
        checkBoolFieldInheritance(value: unknown): boolean {
            return typeof value !== 'boolean';
        },
    },
});
