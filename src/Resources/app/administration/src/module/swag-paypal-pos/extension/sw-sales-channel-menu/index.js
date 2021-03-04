import { PAYPAL_POS_SALES_CHANNEL_TYPE_ID } from '../../../../constant/swag-paypal.constant';

const { Component } = Shopware;

Component.override('sw-sales-channel-menu', {
    methods: {
        createMenuTree() {
            this.$super('createMenuTree');

            const posIds = [];
            this.salesChannels.forEach((salesChannel) => {
                if (salesChannel.typeId === PAYPAL_POS_SALES_CHANNEL_TYPE_ID) {
                    posIds.push(salesChannel.id);
                }
            });

            this.menuItems.forEach((menuItem) => {
                if (posIds.includes(menuItem.id)) {
                    menuItem.path = 'swag.paypal.pos.detail';
                }
            });
        }
    }
});
