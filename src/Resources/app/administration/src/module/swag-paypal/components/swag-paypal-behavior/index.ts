import type * as PayPal from 'src/types';
import template from './swag-paypal-behavior.html.twig';
import constants from '../../page/swag-paypal/swag-paypal-consts';

const { Criteria } = Shopware.Data;

export default Shopware.Component.wrapComponentConfig({
    template,

    inject: [
        'repositoryFactory',
        'acl',
    ],

    props: {
        actualConfigData: {
            type: Object as PropType<PayPal.SystemConfig>,
            required: true,
            default: () => { return { null: {} }; },
        },
        allConfigs: {
            type: Object as PropType<Record<string, PayPal.SystemConfig>>,
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
            /**
             * @deprecated tag:v10.0.0 - Will be removed, use constants directly
             */
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
         * @deprecated tag:v10.0.0 Will be removed without replacement.
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

        /**
         * @deprecated tag:v10.0.0 - Will be removed without replacement.
         */
        showMerchantLocation() {
            return this.actualConfigData['SwagPayPal.settings.plusCheckoutEnabled'] ??
                this.allConfigs?.null?.['SwagPayPal.settings.plusCheckoutEnabled'];
        },

        landingPageOptions() {
            return [
                {
                    id: 'LOGIN',
                    name: this.$tc('swag-paypal.settingForm.behavior.landingPage.options.login'),
                },
                {
                    id: 'GUEST_CHECKOUT',
                    name: this.$tc('swag-paypal.settingForm.behavior.landingPage.options.guest_checkout'),
                },
                {
                    id: 'NO_PREFERENCE',
                    name: this.$tc('swag-paypal.settingForm.behavior.landingPage.options.no_preference'),
                },
            ];
        },

        landingPageHint() {
            const landingPageOption = this.actualConfigData['SwagPayPal.settings.landingPage'] || 'NO_PREFERENCE';
            const translationKey =
                `swag-paypal.settingForm.behavior.landingPage.helpText.${landingPageOption.toLowerCase()}`;
            return this.$tc(translationKey);
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
        /**
         * @deprecated tag:v10.0.0 - Will be removed and is replaced by swag-paypal-inherit-wrapper
         */
        checkTextFieldInheritance(value: unknown): boolean {
            if (typeof value !== 'string') {
                return true;
            }

            return value.length <= 0;
        },

        /**
         * @deprecated tag:v10.0.0 - Will be removed and is replaced by swag-paypal-inherit-wrapper
         */
        checkArrayFieldInheritance(value: unknown): boolean {
            if (!Array.isArray(value)) {
                return true;
            }

            return value.length <= 0;
        },

        /**
         * @deprecated tag:v10.0.0 - Will be removed and is replaced by swag-paypal-inherit-wrapper
         */
        checkBoolFieldInheritance(value: unknown): boolean {
            return typeof value !== 'boolean';
        },
    },
});
