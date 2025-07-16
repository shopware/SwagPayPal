import { SwagPayPalDefaults } from "SwagPayPal/defaults";

export default Shopware.Component.wrapComponentConfig<{ onCloseModal: () => void }>({
    methods: {
        onAddChannel(id: string | null) {
            if (id === SwagPayPalDefaults.agentCommerceTypeId) {
                this.onCloseModal();

                if (id) {
                    this.$router.push({
                        name: 'swag.paypal.agent.commerce.create',
                        params: { typeId: id },
                    });
                }
            }

            this.$super('onAddChannel', id);
        },

        isProductComparisonSalesChannelType(salesChannelTypeId: string): boolean {
            if (salesChannelTypeId === SwagPayPalDefaults.agentCommerceTypeId) {
                return true;
            }

            return this.$super('isProductComparisonSalesChannelType', salesChannelTypeId) as boolean;
        },
    },
});
