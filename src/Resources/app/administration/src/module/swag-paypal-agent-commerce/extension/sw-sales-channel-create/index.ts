const insertIdIntoRoute = (to, from, next) => {
    if (to.name.includes('swag.paypal.agent.commerce.create') && !to.params.id) {
        to.params.id = Shopware.Utils.createId();
    }

    next();
};


export default Shopware.Component.wrapComponentConfig<{ salesChannel: TEntity<'sales_channel'>; isSaveSuccessful: boolean }>({
    beforeRouteEnter: insertIdIntoRoute,

    beforeRouteUpdate: insertIdIntoRoute,

    computed: {
        isProductComparison(): true {
            return true;
        },
    },

    methods: {
        saveFinish() {
            this.isSaveSuccessful = false;
            this.$router.push({
                name: 'swag.paypal.agent.commerce.detail',
                params: { id: this.salesChannel.id },
            });
        },
    },
});
