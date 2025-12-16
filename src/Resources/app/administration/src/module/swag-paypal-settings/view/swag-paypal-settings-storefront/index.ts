import template from './swag-paypal-settings-storefront.html.twig';
import { BUTTON_COLORS, BUTTON_SHAPES } from 'SwagPayPal/constant/swag-paypal-settings.constant';
import type EntityCollection from "@shopware-ag/meteor-admin-sdk/es/_internals/data/EntityCollection";

const { Criteria } = Shopware.Data;

export default Shopware.Component.wrapComponentConfig({
    template,

    inject: [
        'systemConfigApiService',
        'repositoryFactory',
    ],

    data() {
        return {
            doubleOptInConfig: false,
            phoneRequiredConfig: false,
        };
    },

    computed: {
        settingsStore() {
            return Shopware.Store.get('swagPayPalSettings');
        },

        buttonColorOptions() {
            return BUTTON_COLORS.map((color) => ({
                value: color,
                label: this.$t(`swag-paypal-settings.options.buttonColor.${color}`),
            }));
        },

        buttonShapeOptions() {
            return BUTTON_SHAPES.map((shape) => ({
                value: shape,
                label: this.$t(`swag-paypal-settings.options.buttonShape.${shape}`),
            }));
        },

        sbpSettingsDisabled(): boolean {
            return !this.settingsStore.salesChannel && !this.settingsStore.getActual('SwagPayPal.settings.spbCheckoutEnabled');
        },

        ecsSettingsDisabled(): boolean {
            return !this.settingsStore.salesChannel
                && !this.settingsStore.getActual('SwagPayPal.settings.ecsDetailEnabled')
                && !this.settingsStore.getActual('SwagPayPal.settings.ecsCartEnabled')
                && !this.settingsStore.getActual('SwagPayPal.settings.ecsOffCanvasEnabled')
                && !this.settingsStore.getActual('SwagPayPal.settings.ecsLoginEnabled')
                && !this.settingsStore.getActual('SwagPayPal.settings.ecsListingEnabled');
        },

        systemConfigRepository() {
            return this.repositoryFactory.create('system_config');
        },

        systemConfigCriteria() {
            const criteria = new Criteria();

            criteria.addFilter(Criteria.equalsAny('configurationKey', ['core.loginRegistration.doubleOptInGuestOrder', 'core.loginRegistration.phoneNumberFieldRequired']));

            if (this.settingsStore.salesChannel) {
                criteria.addFilter(Criteria.equalsAny('salesChannelId', [this.settingsStore.salesChannel, null]));
            }

            return criteria;
        },
    },

    watch: {
        'settingsStore.salesChannel': {
            immediate: true,
            handler() {
                this.fetchDoubleOptIn();
            },
        },
    },

    methods: {
        async fetchDoubleOptIn() {
            const response = await this.systemConfigRepository.search(this.systemConfigCriteria);

            this.doubleOptInConfig = this.getInheritedConfigValue(response, 'core.loginRegistration.doubleOptInGuestOrder');
            this.phoneRequiredConfig = this.getInheritedConfigValue(response, 'core.loginRegistration.phoneNumberFieldRequired');
        },

        getInheritedConfigValue(response: EntityCollection<"system_config">, key: string): boolean {
            if (!this.settingsStore.salesChannel) {
                return response.some((config) => config.configurationKey === key && config.configurationValue === 'true');
            }

            const inheritedConfig = response.find((config) => !config.salesChannelId && config.configurationKey === key);
            const specificConfig = response.find((config) => config.salesChannelId === this.settingsStore.salesChannel && config.configurationKey === key);

            if (!specificConfig) {
                return inheritedConfig?.configurationValue === 'true';
            }

            return specificConfig.configurationValue === 'true';
        },
    },
});

