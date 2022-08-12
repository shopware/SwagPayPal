import template from './sw-settings-shipping-detail.html.twig';
import './sw-settings-shipping-detail.scss';

const { Component } = Shopware;

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

    methods: {
        createdComponent() {
            this.$super('createdComponent');

            this.fetchMerchantIntegrations();
        },

        fetchMerchantIntegrations() {
            if (!this.shippingMethod.customFields) {
                this.shippingMethod.customFields = {};
            }

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

