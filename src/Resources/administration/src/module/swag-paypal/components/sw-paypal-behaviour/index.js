import template from './swag-paypal-behaviour.html.twig';

const { Component } = Shopware;

Component.register('sw-paypal-behaviour', {
    template,
    name: 'SwagPaypalBehaviour',

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
            type: String,
            required: true
        }
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
            if (typeof value !== 'boolean') {
                return true;
            }

            return false;
        }
    }
});
