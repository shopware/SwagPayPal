import template from './swag-paypal-settings.html.twig';

export default Shopware.Component.wrapComponentConfig({
    template,

    inject: [
        'acl',
    ],

    mixins: [
        Shopware.Mixin.getByName('swag-paypal-settings'),
        Shopware.Mixin.getByName('swag-paypal-merchant-information'),
    ],

    metaInfo() {
        return {
            title: this.$createTitle(),
        };
    },
});
