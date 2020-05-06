import { IZETTLE_SALES_CHANNEL_TYPE_ID } from '../../swag-paypal-izettle-consts';

const { Component } = Shopware;

Component.override('sw-sales-channel-modal', {

    methods: {
        onAddChannel(salesChannelTypeId) {
            if (this.isIZettleSalesChannel(salesChannelTypeId)) {
                this.onCloseModal();
                this.$router.push({ name: 'swag.paypal.izettle.wizard' });
                return;
            }

            this.$super('onAddChannel', salesChannelTypeId);
        },

        isIZettleSalesChannel(salesChannelTypeId) {
            return salesChannelTypeId === IZETTLE_SALES_CHANNEL_TYPE_ID;
        }
    }
});
