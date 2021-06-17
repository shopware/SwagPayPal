import { PAYPAL_POS_SALES_CHANNEL_TYPE_ID } from '../../../../constant/swag-paypal.constant';

const { Component } = Shopware;

Component.override('sw-sales-channel-modal', {

    methods: {
        onAddChannel(salesChannelTypeId) {
            if (this.isPayPalPosSalesChannel(salesChannelTypeId)) {
                this.onCloseModal();
                this.$router.push({ name: 'swag.paypal.pos.wizard' });

                return;
            }

            this.$super('onAddChannel', salesChannelTypeId);
        },

        isPayPalPosSalesChannel(salesChannelTypeId) {
            return salesChannelTypeId === PAYPAL_POS_SALES_CHANNEL_TYPE_ID;
        },
    },
});
