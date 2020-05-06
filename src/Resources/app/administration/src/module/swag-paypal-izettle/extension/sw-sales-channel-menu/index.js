const { Component } = Shopware;

Component.override('sw-sales-channel-menu', {

    methods: {
        createMenuTree() {
            this.$super('createMenuTree');

            const iZettleIds = [];
            this.salesChannels.forEach((salesChannel) => {
                if (salesChannel.extensions.hasOwnProperty('paypalIZettleSalesChannel')) {
                    iZettleIds.push(salesChannel.id);
                }
            });

            this.menuItems.forEach((menuItem) => {
                if (iZettleIds.includes(menuItem.id)) {
                    menuItem.path = 'swag.paypal.izettle.detail';
                }
            });
        }
    }
});
