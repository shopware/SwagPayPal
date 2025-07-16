import { SwagPayPalDefaults } from "SwagPayPal/defaults";

export default Shopware.Component.wrapComponentConfig<{ onCloseModal: () => void }>({
    methods: {
        onAddChannel(id: string | null) {
            if (!id) {
                this.$super('onAddChannel', id);
            }

            if (id === SwagPayPalDefaults.agentCommerceTypeId) {
                this.onCloseModal();

                if (id) {
                    this.$router.push({
                        name: 'swag.paypal.agent.commerce.create',
                        params: { typeId: id },
                    });
                }
            }
        },
    },
});
