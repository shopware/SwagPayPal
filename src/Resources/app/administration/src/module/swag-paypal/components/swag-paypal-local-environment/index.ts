import type * as PayPal from 'SwagPayPal/types';
import template from './swag-paypal-local-environment.html.twig';
import './swag-paypal-local-environment.scss';

/**
 * @deprecated tag:v10.0.0 - Will be replaced by `swag-paypal-settings-advanced`
 */
export default Shopware.Component.wrapComponentConfig({
    template,

    inject: [
        'acl',
    ],

    props: {
        isLoading: {
            type: Boolean,
            required: false,
            default: false,
        },
        actualConfigData: {
            type: Object as PropType<Record<string, PayPal.SystemConfig>>,
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
});
