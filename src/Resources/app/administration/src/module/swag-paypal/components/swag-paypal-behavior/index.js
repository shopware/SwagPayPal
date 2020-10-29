import template from './swag-paypal-behavior.html.twig';
import constants from './../../page/swag-paypal/swag-paypal-consts';
import './swag-paypal-behavior.scss';

const { Component } = Shopware;

Component.register('swag-paypal-behavior', {
    template,

    inject: [
        'acl'
    ],

    props: {
        actualConfigData: {
            type: Object,
            required: true
        },
        allConfigs: {
            type: Object,
            required: true
        },
        selectedSalesChannelId: {
            required: true
        }
    },

    data() {
        return {
            ...constants
        };
    },

    computed: {
        intentOptions() {
            return [
                {
                    id: 'sale',
                    name: this.$tc('swag-paypal.settingForm.behavior.intent.sale')
                },
                {
                    id: 'authorize',
                    name: this.$tc('swag-paypal.settingForm.behavior.intent.authorize')
                },
                {
                    id: 'order',
                    name: this.$tc('swag-paypal.settingForm.behavior.intent.order')
                }
            ];
        },
        merchantLocationOptions() {
            return [
                {
                    id: this.MERCHANT_LOCATION_GERMANY,
                    name: this.$tc('swag-paypal.settingForm.behavior.merchantLocation.germany')
                },
                {
                    id: this.MERCHANT_LOCATION_OTHER,
                    name: this.$tc('swag-paypal.settingForm.behavior.merchantLocation.other')
                }
            ];
        },
        landingPageOptions() {
            return [
                {
                    id: 'Login',
                    name: this.$tc('swag-paypal.settingForm.behavior.landingPage.options.Login')
                },
                {
                    id: 'Billing',
                    name: this.$tc('swag-paypal.settingForm.behavior.landingPage.options.Billing')
                }
            ];
        }
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
        }
    }
});
