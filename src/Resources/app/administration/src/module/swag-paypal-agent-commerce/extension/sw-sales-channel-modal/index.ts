export default Shopware.Component.wrapComponentConfig({
    methods: {
        onAddChannel(id) {
            if (!id) {
                this.$super('onAddChannel', id);
            }

            if (id === 'e3f8c9b2f1a44d4db0f793542e31d2c9') {
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
