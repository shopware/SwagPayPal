import template from './swag-paypal-plus.html.twig';

const { Component } = Shopware;

/**
 * @deprecated tag:v6.0.0 - Will be removed without replacement.
 */
Component.register('swag-paypal-plus', {
    template,

    inject: [
        'acl',
    ],

    props: {
        actualConfigData: {
            type: Object,
            required: true,
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
        checkTextFieldInheritance(value) {
            if (typeof value !== 'string') {
                return true;
            }

            return value.length <= 0;
        },

        checkBoolFieldInheritance(value) {
            return typeof value !== 'boolean';
        },
    },
});
