import type * as PayPal from 'SwagPayPal/types';
import template from './swag-paypal-express.html.twig';
import type EntityCollection from "@shopware-ag/meteor-admin-sdk/es/_internals/data/EntityCollection";

const { Criteria } = Shopware.Data;

/**
 * @deprecated tag:v10.0.0 - Will be replaced by `swag-paypal-settings-storefront`
 */
export default Shopware.Component.wrapComponentConfig({
    template,

    inject: ['acl', 'repositoryFactory'],

    props: {
        actualConfigData: {
            type: Object as PropType<PayPal.SystemConfig>,
            required: true,
            default: () => { return {}; },
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
            doubleOptInConfig: false,
            phoneRequiredConfig: false,
        };
    },

    computed: {
        buttonColorOptions() {
            return [
                {
                    id: 'blue',
                    name: this.$tc('swag-paypal.settingForm.express.ecsButtonColor.options.blue'),
                },
                {
                    id: 'black',
                    name: this.$tc('swag-paypal.settingForm.express.ecsButtonColor.options.black'),
                },
                {
                    id: 'gold',
                    name: this.$tc('swag-paypal.settingForm.express.ecsButtonColor.options.gold'),
                },
                {
                    id: 'silver',
                    name: this.$tc('swag-paypal.settingForm.express.ecsButtonColor.options.silver'),
                },
                {
                    id: 'white',
                    name: this.$tc('swag-paypal.settingForm.express.ecsButtonColor.options.white'),
                },
            ];
        },
        buttonShapeOptions() {
            return [
                {
                    id: 'sharp',
                    name: this.$tc('swag-paypal.settingForm.express.ecsButtonShape.options.sharp'),
                },
                {
                    id: 'pill',
                    name: this.$tc('swag-paypal.settingForm.express.ecsButtonShape.options.pill'),
                },
                {
                    id: 'rect',
                    name: this.$tc('swag-paypal.settingForm.express.ecsButtonShape.options.rect'),
                },
            ];
        },

        renderSettingsDisabled() {
            return !this.acl.can('swag_paypal.editor') || (
                !this.selectedSalesChannelId
                && !this.actualConfigData['SwagPayPal.settings.ecsDetailEnabled']
                && !this.actualConfigData['SwagPayPal.settings.ecsCartEnabled']
                && !this.actualConfigData['SwagPayPal.settings.ecsOffCanvasEnabled']
                && !this.actualConfigData['SwagPayPal.settings.ecsLoginEnabled']
                && !this.actualConfigData['SwagPayPal.settings.ecsListingEnabled']
            );
        },

        systemConfigRepository(): TRepository<'system_config'> {
            return this.repositoryFactory.create('system_config');
        },

        systemConfigCriteria(): TCriteria {
            const criteria = new Criteria();

            criteria.addFilter(Criteria.equalsAny('configurationKey', ['core.loginRegistration.doubleOptInGuestOrder', 'core.loginRegistration.phoneNumberFieldRequired']));

            if (this.selectedSalesChannelId) {
                criteria.addFilter(Criteria.equalsAny('salesChannelId', [this.selectedSalesChannelId, null]));
            }

            return criteria;
        },
    },

    watch: {
        selectedSalesChannelId: {
            immediate: true,
            handler() {
                this.fetchSystemConfig();
            },
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
        checkBoolFieldInheritance(value: unknown): boolean {
            return typeof value !== 'boolean';
        },

        async fetchSystemConfig(): Promise<void> {
            const response = await this.systemConfigRepository.search(this.systemConfigCriteria);

            this.doubleOptInConfig = this.getInheritedConfigValue(response, 'core.loginRegistration.doubleOptInGuestOrder');
            this.phoneRequiredConfig = this.getInheritedConfigValue(response, 'core.loginRegistration.phoneNumberFieldRequired');
        },

        getInheritedConfigValue(response: EntityCollection<"system_config">, key: string): boolean {
            if (!this.selectedSalesChannelId) {
                return response.some((config) => config.configurationKey === key && config.configurationValue === true);
            }

            const inheritedConfig = response.find((config) => !config.salesChannelId && config.configurationKey === key);
            const specificConfig = response.find((config) => config.salesChannelId === this.selectedSalesChannelId && config.configurationKey === key);

            if (!specificConfig) {
                return inheritedConfig?.configurationValue === true;
            }

            return specificConfig.configurationValue === true;
        },

        preventSave(mode: boolean) {
            this.$emit('preventSave', mode);
        },
    },
});
