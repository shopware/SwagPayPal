import type { RouteLocationNormalized, NavigationGuardNext, Router } from 'vue-router';

const insertIdIntoRoute = (to: RouteLocationNormalized, _: RouteLocationNormalized, next: NavigationGuardNext) => {
    if (to.name?.toString().includes('swag.paypal.agent.commerce.create') && !to.params.id) {
        to.params.id = Shopware.Utils.createId();
    }

    next();
};


export default Shopware.Component.wrapComponentConfig({
    beforeRouteEnter: insertIdIntoRoute,

    beforeRouteUpdate: insertIdIntoRoute,

    computed: {
        isProductComparison(): true {
            return true;
        },
    },

    methods: {
        saveFinish(this: { isSaveSuccessful: boolean; $router: Router; salesChannel: { id: string } }) {
            this.isSaveSuccessful = false;
            this.$router.push({
                name: 'swag.paypal.agent.commerce.detail',
                params: { id: this.salesChannel.id },
            });
        },
    },
});
