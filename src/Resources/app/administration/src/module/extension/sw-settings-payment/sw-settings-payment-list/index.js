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

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.fetchMerchantIntegrations();
        },

        needsOnboarding(item) {
            const integrationIds = Object.keys(this.merchantIntegrations);

            if (!integrationIds.includes(item.id)) {
                return false;
            }

            return this.merchantIntegrations[item.id].toUpperCase() === 'INACTIVE';
        },

        fetchMerchantIntegrations() {
            this.SwagPayPalApiCredentialsService
                .getMerchantIntegrations()
                .then((response) => {
                    this.merchantIntegrations = response;
                });
        },
    },
});
