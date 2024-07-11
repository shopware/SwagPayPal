import template from './sw-order-user-card.html.twig';
import './sw-order-user-card.scss';

const { Component } = Shopware;

Component.override('sw-order-user-card', {
    template,

    inject: [
        'systemConfigApiService',
    ],

    data() {
        return {
            isPayPalSandbox: false,
        };
    },

    computed: {
        transaction() {
            return this.currentOrder.transactions?.last();
        },

        payPalResourceId() {
            return this.transaction?.customFields?.swag_paypal_resource_id;
        },

        payPalCarrier() {
            return this.delivery?.shippingMethod?.customFields?.swag_paypal_carrier ?? '';
        },

        payPalOrderLink() {
            const prefix = this.isPayPalSandbox ? 'sandbox' : 'www';

            return `https://${prefix}.paypal.com/activity/payment/${this.payPalResourceId}`;
        },

        payPalCarrierDescription() {
            return this.$tc('sw-order-detail.payPalCarrierDescription', 1, { orderLink: this.payPalOrderLink });
        },
    },

    watch: {
        payPalResourceId: {
            async handler(value) {
                if (!value) {
                    return;
                }

                const salesChannelConfig = await this.systemConfigApiService.getValues(
                    'SwagPayPal.settings',
                    this.currentOrder.salesChannelId,
                );

                if (Object.hasOwn(salesChannelConfig, 'SwagPayPal.settings.sandbox')) {
                    this.isPayPalSandbox = salesChannelConfig['SwagPayPal.settings.sandbox'];
                }

                const config = await this.systemConfigApiService.getValues('SwagPayPal.settings');

                this.isPayPalSandbox = config['SwagPayPal.settings.sandbox'];
            },
            immediate: true,
        },
    },
});
