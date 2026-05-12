import template from './swag-paypal-method-merchant-information.html.twig';
import './swag-paypal-method-merchant-information.scss';

export default Shopware.Component.wrapComponentConfig({
    template,

    emits: ['save'],

    computed: {
        settingsStore() {
            return Shopware.Store.get('swagPayPalSettings');
        },

        merchantInformationStore() {
            return Shopware.Store.get('swagPayPalMerchantInformation');
        },

        merchantEmail() {
            return this.merchantInformationStore.actual.merchantIntegrations?.primary_email
                ?? this.merchantInformationStore.actual.merchantIntegrations?.tracking_id;
        },

        sandboxToggleDisabled(): boolean {
            if (this.settingsStore.salesChannel) {
                return false;
            }

            if (this.settingsStore.isSandbox) {
                return !!this.settingsStore.get('SwagPayPal.settings.clientSecretSandbox') && !this.settingsStore.get('SwagPayPal.settings.clientSecret');
            }

            return !!this.settingsStore.get('SwagPayPal.settings.clientSecret') && !this.settingsStore.get('SwagPayPal.settings.clientSecretSandbox');
        },

        tooltipSandbox() {
            return this.settingsStore.isSandbox
                ? this.$t('swag-paypal-method.sandbox.onlySandboxTooltip')
                : this.$t('swag-paypal-method.sandbox.onlyLiveTooltip');
        },
    },

    methods: {
        onSandboxToggle() {
            this.$emit('save');
        },
    },
});
