import template from './sw-sales-channel-detail-base.html.twig';

export default Shopware.Component.wrapComponentConfig({
    template,

    computed: {
        isProductComparison() {
            return true;
        },
    },
});
