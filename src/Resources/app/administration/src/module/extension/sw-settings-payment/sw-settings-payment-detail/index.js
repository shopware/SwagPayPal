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
            merchantIntegrations: [],
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
        },

        needsOnboarding(id) {
            const integrationIds = Object.keys(this.merchantIntegrations);

            if (!integrationIds.includes(id)) {
                return false;
            }

            return this.merchantIntegrations[id].toUpperCase() === 'INACTIVE';
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

