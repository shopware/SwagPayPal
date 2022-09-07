import template from './sw-settings-shipping-detail.html.twig';
import './sw-settings-shipping-detail.scss';

const { Component, Utils } = Shopware;

Component.override('sw-settings-shipping-detail', {
    template,

    inject: [
        'SwagPayPalApiCredentialsService',
    ],

    data() {
        return {
            isPayPalEnabled: false,
        };
    },

    computed: {
        shippingMethodCustomFields() {
            if (this.shippingMethod.customFields) {
                return this.shippingMethod.customFields;
            }

            return Utils.object.get(this.shippingMethod, 'translated.customFields', null);
        },

        payPalDefaultCarrier: {
            get() {
                if (this.shippingMethodCustomFields === null) {
                    return '';
                }

                return this.shippingMethodCustomFields.swag_paypal_carrier || '';
            },
            set(value) {
                Utils.object.set(this.shippingMethod, 'customFields.swag_paypal_carrier', value);
            },
        },
    },

    methods: {
        createdComponent() {
            this.$super('createdComponent');

            this.fetchMerchantIntegrations();
        },

        fetchMerchantIntegrations() {
            this.SwagPayPalApiCredentialsService
                .getMerchantInformation()
                .then((response) => {
                    this.isPayPalEnabled =
                        response.hasOwnProperty('merchantIntegrations')
                        && response.merchantIntegrations !== null;
                });
        },
    },
});

