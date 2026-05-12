import type * as PayPal from 'SwagPayPal/types';
import template from './swag-paypal-settings-general.html.twig';
import './swag-paypal-settings-general.scss';
import { INTENTS, LANDING_PAGES } from 'SwagPayPal/constant/swag-paypal-settings.constant';

const { Criteria } = Shopware.Data;

export default Shopware.Component.wrapComponentConfig({
    template,

    inject: [
        'acl',
        'repositoryFactory',
        'SwagPayPalSettingsService',
    ],

    mixins: [
        Shopware.Mixin.getByName('swag-paypal-notification'),
    ],

    data(): {
        testCredentials: 'none' | 'loading' | 'success';
        testCredentialsSandbox: 'none' | 'loading' | 'success';
    } {
        return {
            testCredentials: 'none',
            testCredentialsSandbox: 'none',
        };
    },

    computed: {
        settingsStore() {
            return Shopware.Store.get('swagPayPalSettings');
        },

        merchantInformationStore() {
            return Shopware.Store.get('swagPayPalMerchantInformation');
        },

        intentOptions() {
            return INTENTS.map((intent) => ({
                value: intent,
                label: this.$t(`swag-paypal-settings.options.intent.${intent}`),
            }));
        },

        landingPageOptions() {
            return LANDING_PAGES.map((landingPage) => ({
                value: landingPage,
                label: this.$t(`swag-paypal-settings.options.landingPage.${landingPage}`),
            }));
        },

        productRepository() {
            return this.repositoryFactory.create('product');
        },

        productStreamRepository() {
            return this.repositoryFactory.create('product_stream');
        },

        excludedProductCriteria() {
            return (new Criteria(1, 25)).addAssociation('options.group');
        },
    },

    methods: {
        async onTest(prefix: 'Sandbox' | '') {
            this[`testCredentials${prefix}`] = 'loading';

            const response = await this.SwagPayPalSettingsService.testApiCredentials(
                this.settingsStore.get(`SwagPayPal.settings.clientId${prefix}`),
                this.settingsStore.get(`SwagPayPal.settings.clientSecret${prefix}`),
                this.settingsStore.get(`SwagPayPal.settings.merchantPayerId${prefix}`),
                prefix === 'Sandbox',
            ).catch((error: PayPal.ServiceError) => error.response?.data ?? { errors: [] });

            if (response.valid) {
                this.createNotificationSuccess({
                    title: this.$t('swag-paypal.notifications.test.title'),
                    message: this.$t('swag-paypal.notifications.test.successMessage'),
                });
            } else if (response.errors) {
                this.createNotificationError({
                    title: this.$t('swag-paypal.notifications.test.title'),
                    message: this.$t('swag-paypal.notifications.test.errorMessage', {
                        message: this.createMessageFromError(response),
                    }),
                });
            }

            this[`testCredentials${prefix}`] = response.valid ? 'success' : 'none';
        },
    },
});
