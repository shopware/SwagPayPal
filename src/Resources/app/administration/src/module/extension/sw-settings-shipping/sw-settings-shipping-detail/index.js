import template from './sw-settings-shipping-detail.html.twig';
import './sw-settings-shipping-detail.scss';

const { Component, Utils } = Shopware;

Component.override('sw-settings-shipping-detail', {
    template,

    inject: [
        'systemConfigApiService',
    ],

    data() {
        return {
            isPayPalEnabled: true,
        };
    },

    computed: {
        shippingMethodCustomFields() {
            if (this.shippingMethod.customFields) {
                return this.shippingMethod.customFields;
            }

            return Utils.object.get(this.shippingMethod, 'translated.customFields', null);
        },

        payPalDefaultCarrier: {
            get() {
                if (this.shippingMethodCustomFields === null) {
                    return '';
                }

                return this.shippingMethodCustomFields.swag_paypal_carrier || '';
            },
            set(value) {
                Utils.object.set(this.shippingMethod, 'customFields.swag_paypal_carrier', value);
            },
        },
    },

    methods: {
        createdComponent() {
            this.$super('createdComponent');

            this.fetchConfigCredentials();
        },

        fetchConfigCredentials() {
            this.systemConfigApiService
                .getValues('SwagPayPal.settings', null)
                .then((values) => {
                    this.isPayPalEnabled = values['SwagPayPal.settings.merchantPayerId']
                        || values['SwagPayPal.settings.merchantPayerIdSandbox'];
                });
        },

        /**
         * @deprecated tag:v10.0.0 - Will be removed, use `fetchConfigCredentials` instead.
         */
        async fetchMerchantIntegrations() {
            const merchantInformation = await this.SwagPayPalApiCredentialsService.getMerchantInformation();

            this.isPayPalEnabled = merchantInformation?.merchantIntegrations !== null;
        },
    },
});

