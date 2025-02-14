import template from './swag-paypal-settings-storefront.html.twig';
import { BUTTON_COLORS, BUTTON_SHAPES } from 'SwagPayPal/constant/swag-paypal-settings.constant';

export default Shopware.Component.wrapComponentConfig({
    template,

    inject: [
        'systemConfigApiService',
    ],

    data() {
        return {
            doubleOptInConfig: false,
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
            const config = await this.systemConfigApiService.getValues('core.loginRegistration.doubleOptInGuestOrder') as Record<string, boolean>;

            this.doubleOptInConfig = !!config['core.loginRegistration.doubleOptInGuestOrder'];
        },
    },
});
