import { POS_SALES_CHANNEL_TYPE_ID } from '../../swag-paypal-pos-consts';

const { Component } = Shopware;

Component.override('sw-sales-channel-modal', {

    methods: {
        onAddChannel(salesChannelTypeId) {
            if (this.isPosSalesChannel(salesChannelTypeId)) {
                this.onCloseModal();
                this.$router.push({ name: 'swag.paypal.pos.wizard' });

                return;
            }

            this.$super('onAddChannel', salesChannelTypeId);
        },

        isPosSalesChannel(salesChannelTypeId) {
            return salesChannelTypeId === POS_SALES_CHANNEL_TYPE_ID;
        }
    }
});
