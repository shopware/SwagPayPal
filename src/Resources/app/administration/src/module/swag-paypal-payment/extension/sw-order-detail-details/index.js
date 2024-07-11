import template from './sw-order-detail-details.html.twig';
import './sw-order-detail-details.scss';

const { Component } = Shopware;

Component.override('sw-order-detail-details', {
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
            return this.order?.transactions.last();
        },

        payPalResourceId() {
            return this.transaction?.customFields?.swag_paypal_resource_id;
        },

        payPalCarrier() {
            return this.order?.deliveries?.first().shippingMethod?.customFields?.swag_paypal_carrier ?? '';
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
                    this.order.salesChannelId,
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
