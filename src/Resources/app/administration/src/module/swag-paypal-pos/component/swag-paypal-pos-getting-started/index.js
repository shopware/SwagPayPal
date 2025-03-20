import template from './swag-paypal-pos-getting-started.html.twig';
import './swag-paypal-pos-getting-started.scss';

const { Component } = Shopware;

Component.register('swag-paypal-pos-getting-started', {
    template,

    computed: {
        assetFilter() {
            return Shopware.Filter.getByName('asset');
        },
    },
});
