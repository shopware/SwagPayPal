const FlatTree = Shopware.Helper.FlatTreeHelper;

export default Shopware.Component.wrapComponentConfig < { salesChannels: TEntityCollection<'sales_channel'>; getDomainLink: (salesChannel: TEntity<'sales_channel'>) => string } >({
    computed: {
        isProductComparison(): boolean {
            return true;
        },

        buildMenuTree(): unknown {
            const flatTree = new FlatTree();

            this.salesChannels.forEach((salesChannel) => {
                const path = salesChannel.type?.id === 'e3f8c9b2f1a44d4db0f793542e31d2c9'
                    ? 'swag.paypal.agent.commerce.detail'
                    : 'sw.sales.channel.detail';

                flatTree.add({
                    id: salesChannel.id,
                    path: path,
                    params: { id: salesChannel.id },
                    color: '#D8DDE6',
                    label: {
                        label: salesChannel.translated?.name,
                        translated: true,
                    },
                    icon: salesChannel.type?.iconName,
                    children: [],
                    domainLink: this.getDomainLink(salesChannel),
                    active: salesChannel.active,
                });
            });

            return flatTree.convertToTree();
        },
    },
});
