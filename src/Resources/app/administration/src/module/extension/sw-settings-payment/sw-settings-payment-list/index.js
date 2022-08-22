import template from './sw-settings-payment-list.html.twig';
import './sw-settings-payment-list.scss';

const { Component } = Shopware;

Component.override('sw-settings-payment-list', {
    template,

    inject: [
        'SwagPayPalApiCredentialsService',
    ],

    data() {
        return {
            merchantIntegrations: [],
        };
    },

    methods: {
        needsOnboarding(item) {
            const integrationIds = Object.keys(this.merchantIntegrations);

            if (!integrationIds.includes(item.id)) {
                return false;
            }

            return this.merchantIntegrations[item.id].toUpperCase() === 'INACTIVE';
        },

        fetchMerchantIntegrations() {
            this.SwagPayPalApiCredentialsService
                .getMerchantInformation()
                .then((response) => {
                    this.merchantIntegrations = response.merchantIntegrations ?? [];
                });
        },
    },
});
