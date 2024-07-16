import type * as PayPal from 'src/types';
import template from './swag-paypal-cross-border.html.twig';
import './swag-paypal-cross-border.scss';

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

    computed: {
        countryOverrideOptions() {
            const options = [{
                label: this.$tc('locale.en-AU'),
                value: 'en-AU',
            }, {
                label: this.$tc('locale.de-DE'),
                value: 'de-DE',
            }, {
                label: this.$tc('locale.es-ES'),
                value: 'es-ES',
            }, {
                label: this.$tc('locale.fr-FR'),
                value: 'fr-FR',
            }, {
                label: this.$tc('locale.en-GB'),
                value: 'en-GB',
            }, {
                label: this.$tc('locale.it-IT'),
                value: 'it-IT',
            }, {
                label: this.$tc('locale.en-US'),
                value: 'en-US',
            }].sort((a, b) => a.label.localeCompare(b.label));

            return [{
                value: null,
                label: this.$tc('swag-paypal.cross-border.crossBorderBuyerCountryAuto'),
            }, ...options];
        },
    },
});
