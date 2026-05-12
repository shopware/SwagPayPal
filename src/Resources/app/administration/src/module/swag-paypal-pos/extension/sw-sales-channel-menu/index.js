import { PAYPAL_POS_SALES_CHANNEL_TYPE_ID } from '../../../../constant/swag-paypal.constant';

const { Component } = Shopware;

Component.override('sw-sales-channel-menu', {
    computed: {
        buildMenuTree() {
            const menuItems = this.$super('buildMenuTree');

            const posIds = [];
            this.salesChannels.forEach((salesChannel) => {
                if (salesChannel.type.id === PAYPAL_POS_SALES_CHANNEL_TYPE_ID) {
                    posIds.push(salesChannel.id);
                }
            });

            menuItems.forEach((menuItem) => {
                if (posIds.includes(menuItem.id)) {
                    menuItem.path = 'swag.paypal.pos.detail';
                }
            });

            return menuItems;
        },
    },
});
