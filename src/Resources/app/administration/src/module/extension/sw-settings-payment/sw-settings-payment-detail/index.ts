import template from './sw-settings-payment-detail.html.twig';
import './sw-settings-payment-detail.scss';

const { Component } = Shopware;

Component.override('sw-settings-payment-detail', {
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
            capabilities: {},
        };
    },

    computed: {
        disableActiveSwitch() {
            return !this.acl.can('payment.editor') || this.needsOnboarding(this.paymentMethod.id);
        },
    },

    methods: {
        createdComponent() {
            this.$super('createdComponent');

            this.fetchMerchantIntegrations();
            this.fetchMerchantCapabilities();
        },

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
            this.SwagPayPalApiCredentialsService
                .getMerchantInformation()
                .then((response) => {
                    this.merchantIntegrations = response.merchantIntegrations ?? [];
                });
        },

        async fetchMerchantCapabilities() {
            const merchantInformation = await this.SwagPayPalApiCredentialsService.getMerchantInformation();

            this.capabilities = merchantInformation.capabilities ?? {};
        },
    },
});

