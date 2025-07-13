import template from './sw-sales-channel-detail.html.twig';

export default Shopware.Component.wrapComponentConfig({
    template,

    computed: {
        isProductComparison() {
            return true;
        },
    },
});
