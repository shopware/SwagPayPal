import template from './swag-paypal-settings.html.twig';

export default Shopware.Component.wrapComponentConfig({
    template,

    compatConfig: Shopware.compatConfig,

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
