import template from './swag-paypal-checkout-domain-association.html.twig';
import './swag-paypal-checkout-domain-association.scss';

const { Component } = Shopware;

Component.register('swag-paypal-checkout-domain-association', {
    template,

    props: {
        isSandbox: {
            required: true,
        },
    },

    computed: {
        domainAssociationLink() {
            return this.isSandbox
                ? 'https://www.sandbox.paypal.com/uccservicing/apm/applepay'
                : 'https://www.paypal.com/uccservicing/apm/applepay';
        },
    },
});
