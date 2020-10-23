import template from './swag-paypal-behaviour.html.twig';
import constants from './../../page/swag-paypal/swag-paypal-consts';
import './swag-paypal-behaviour.scss';

const { Component } = Shopware;

Component.register('swag-paypal-behaviour', {
    template,

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
                    name: this.$tc('swag-paypal.settingForm.behaviour.intent.sale')
                },
                {
                    id: 'authorize',
                    name: this.$tc('swag-paypal.settingForm.behaviour.intent.authorize')
                },
                {
                    id: 'order',
                    name: this.$tc('swag-paypal.settingForm.behaviour.intent.order')
                }
            ];
        },
        merchantLocationOptions() {
            return [
                {
                    id: this.MERCHANT_LOCATION_GERMANY,
                    name: this.$tc('swag-paypal.settingForm.behaviour.merchantLocation.germany')
                },
                {
                    id: this.MERCHANT_LOCATION_OTHER,
                    name: this.$tc('swag-paypal.settingForm.behaviour.merchantLocation.other')
                }
            ];
        },
        landingPageOptions() {
            return [
                {
                    id: 'Login',
                    name: this.$tc('swag-paypal.settingForm.behaviour.landingPage.options.Login')
                },
                {
                    id: 'Billing',
                    name: this.$tc('swag-paypal.settingForm.behaviour.landingPage.options.Billing')
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
