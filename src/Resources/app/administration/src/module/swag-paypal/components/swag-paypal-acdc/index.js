import template from './swag-paypal-acdc.html.twig';

const { Component } = Shopware;

Component.register('swag-paypal-acdc', {
    template,

    inject: [
        'acl',
    ],

    props: {
        actualConfigData: {
            type: Object,
            required: true,
            default: () => { return {}; },
        },
        allConfigs: {
            type: Object,
            required: true,
        },
        selectedSalesChannelId: {
            type: String,
            required: false,
            default: null,
        },
    },

    methods: {
        checkBoolFieldInheritance(value) {
            return typeof value !== 'boolean';
        },
    },
});
