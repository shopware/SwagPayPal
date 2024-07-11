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
            /**
             * @deprecated tag:v10.0.0 - Will be removed, use this.capabilities instead
             */
            merchantIntegrations: [],
            capabilities: [],
        };
    },

    methods: {
        needsOnboarding(id) {
            const capabilityIds = Object.keys(this.capabilities);

            if (!capabilityIds.includes(id)) {
                return false;
            }

            return this.capabilities[id].toUpperCase() === 'INACTIVE';
        },

        /**
         * @deprecated tag:v10.0.0 - Will be removed, use this.fetchMerchantCapabilities instead
         */
        fetchMerchantIntegrations() {
            this.fetchMerchantCapabilities();
        },

        async fetchMerchantCapabilities() {
            const merchantInformation = await this.SwagPayPalApiCredentialsService.getMerchantInformation();

            this.capabilities = merchantInformation.capabilities ?? [];

            this.merchantIntegrations = merchantInformation.merchantIntegrations ?? [];
        },
    },
});
