import template from './swag-paypal-behavior.html.twig';
import constants from '../../page/swag-paypal/swag-paypal-consts';

const { Component } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('swag-paypal-behavior', {
    template,

    inject: [
        'repositoryFactory',
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

    data() {
        return {
            ...constants,
        };
    },

    computed: {
        intentOptions() {
            return [
                {
                    id: 'CAPTURE',
                    name: this.$tc('swag-paypal.settingForm.behavior.intent.CAPTURE'),
                },
                {
                    id: 'AUTHORIZE',
                    name: this.$tc('swag-paypal.settingForm.behavior.intent.AUTHORIZE'),
                },
            ];
        },

        /**
         * @deprecated tag:v6.0.0 Will be removed without replacement.
         */
        merchantLocationOptions() {
            return [
                {
                    id: this.MERCHANT_LOCATION_GERMANY,
                    name: this.$tc('swag-paypal.settingForm.behavior.merchantLocation.germany'),
                },
                {
                    id: this.MERCHANT_LOCATION_OTHER,
                    name: this.$tc('swag-paypal.settingForm.behavior.merchantLocation.other'),
                },
            ];
        },

        landingPageOptions() {
            return [
                {
                    id: 'LOGIN',
                    name: this.$tc('swag-paypal.settingForm.behavior.landingPage.options.login'),
                },
                {
                    id: 'BILLING',
                    name: this.$tc('swag-paypal.settingForm.behavior.landingPage.options.billing'),
                },
                {
                    id: 'NO_PREFERENCE',
                    name: this.$tc('swag-paypal.settingForm.behavior.landingPage.options.no_preference'),
                },
            ];
        },

        landingPageHint() {
            let landingPageOption = this.actualConfigData['SwagPayPal.settings.landingPage'] ||
                this.allConfigs.null['SwagPayPal.settings.landingPage'] || 'NO_PREFERENCE';
            landingPageOption = landingPageOption.toLowerCase();
            const translationKey = `swag-paypal.settingForm.behavior.landingPage.helpText.${landingPageOption}`;
            return this.$tc(translationKey);
        },

        loggingLevelOptions() {
            return [
                {
                    value: 300,
                    label: this.$tc('swag-paypal.settingForm.behavior.loggingLevel.options.basic'),
                },
                {
                    value: 100,
                    label: this.$tc('swag-paypal.settingForm.behavior.loggingLevel.options.advanced'),
                },
            ];
        },

        productRepository() {
            return this.repositoryFactory.create('product');
        },

        productStreamRepository() {
            return this.repositoryFactory.create('product_stream');
        },

        excludedProductCriteria() {
            const criteria = new Criteria(1, 25);
            criteria.addAssociation('options.group');

            return criteria;
        },
    },

    methods: {
        checkTextFieldInheritance(value) {
            if (typeof value !== 'string') {
                return true;
            }

            return value.length <= 0;
        },

        checkArrayFieldInheritance(value) {
            if (!Array.isArray(value)) {
                return true;
            }

            return value.length <= 0;
        },

        checkBoolFieldInheritance(value) {
            return typeof value !== 'boolean';
        },
    },
});
