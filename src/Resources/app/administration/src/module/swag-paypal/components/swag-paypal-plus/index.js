import template from './swag-paypal-plus.html.twig';
import './swag-paypal-plus.scss';

const { Component } = Shopware;

/**
 * @deprecated tag:v7.0.0 - Will be removed without replacement.
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

    computed: {
        isPayPalPLUSActive() {
            return this.actualConfigData['SwagPayPal.settings.plusCheckoutEnabled'];
        },

        isPayPalPLUSInActive() {
            return !this.isPayPalPLUSActive;
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

        ifItWasNotActive() {
            return !this.actualConfigData['SwagPayPal.settings.plusCheckoutEnabled'];
        },
    },
});
