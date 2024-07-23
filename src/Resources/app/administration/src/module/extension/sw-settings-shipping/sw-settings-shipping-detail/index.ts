import type * as PayPal from 'src/types';
import { ref } from 'vue';
import template from './sw-settings-shipping-detail.html.twig';
import './sw-settings-shipping-detail.scss';
import carriers, { commonCarriers } from '../../../../constant/swag-paypal-carrier.constant';

const { Utils } = Shopware;

type CustomFields = TEntity<'shipping_method'>['customFields'];
type SwSingleSelect = {
    results: object[];
};

export default Shopware.Component.wrapComponentConfig({
    template,

    inject: [
        'systemConfigApiService',
    ],

    data(): {
        isPayPalEnabled: boolean;
        limit: number;
        searchTerm: string | null;
        commonCarriers: typeof commonCarriers;
    } {
        return {
            isPayPalEnabled: true,
            limit: 50,
            searchTerm: null,
            commonCarriers,
        };
    },

    setup() {
        return {
            carrierSelect: ref<SwSingleSelect>({ results: [] }),
        };
    },

    computed: {
        shippingMethodCustomFields(): CustomFields {
            const shippingMethod = this.shippingMethod as TEntity<'shipping_method'>;

            return shippingMethod.customFields;
        },

        payPalDefaultCarrier: {
            get(): string {
                return this.shippingMethodCustomFields?.swag_paypal_carrier || '';
            },
            set(value?: string) {
                if (value === 'OTHER' && this.selectedCarrierOption?.isInvalid) {
                    this.payPalOtherCarrierName = this.payPalDefaultCarrier;
                }

                this.setCustomFieldValue('swag_paypal_carrier', value || '');
            },
        },

        payPalOtherCarrierName: {
            get(): string {
                return this.shippingMethodCustomFields?.swag_paypal_carrier_other_name || '';
            },
            set(value?: string) {
                this.setCustomFieldValue('swag_paypal_carrier_other_name', value || '');
            },
        },

        selectedCarrierOption() {
            const validCarrier = carriers.find((carrier) => carrier.value === this.payPalDefaultCarrier);

            if (!validCarrier && this.payPalDefaultCarrier) {
                return {
                    value: this.payPalDefaultCarrier,
                    description: this.payPalDefaultCarrier,
                    isInvalid: true,
                };
            }

            return validCarrier;
        },

        carrierOptions() {
            const selected = this.selectedCarrierOption && !this.searchTerm ? [this.selectedCarrierOption] : [];

            const options = carriers
                .filter((carrier) => {
                    return carrier.value !== this.selectedCarrierOption?.value && (!this.searchTerm
                        || carrier.description.toLowerCase().includes(this.searchTerm.toLowerCase())
                        || carrier.value.toLowerCase().includes(this.searchTerm.toLowerCase()));
                });

            options.splice(this.limit);
            options.splice(0, 0, ...selected);

            return options;
        },

        carrierInvalidError() {
            if (this.selectedCarrierOption?.isInvalid) {
                return { detail: this.$tc('swag-paypal-settings-shipping-carrier.invalid') };
            }

            return undefined;
        },
    },

    methods: {
        createdComponent() {
            this.$super('createdComponent');

            this.fetchConfigCredentials();
        },

        setCustomFieldValue<T extends keyof CustomFields>(field: T, value: CustomFields[T]) {
            Utils.object.set(this.shippingMethod as TEntity<'shipping_method'>, `customFields.${field}`, value);
        },

        paginateCarriers() {
            this.limit += 50;

            this.carrierSelect.results = this.carrierOptions;
        },

        searchCarriers(searchTerm: string | null) {
            this.limit = 50;
            this.searchTerm = searchTerm;

            this.carrierSelect.results = this.carrierOptions;
        },

        onCarrierSelectOpen() {
            this.limit = 50;
            this.searchTerm = null;
        },

        fetchConfigCredentials() {
            this.systemConfigApiService
                .getValues('SwagPayPal.settings', null)
                .then((values: PayPal.SystemConfig) => {
                    this.isPayPalEnabled = !!values['SwagPayPal.settings.merchantPayerId']
                        || !!values['SwagPayPal.settings.merchantPayerIdSandbox'];
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
