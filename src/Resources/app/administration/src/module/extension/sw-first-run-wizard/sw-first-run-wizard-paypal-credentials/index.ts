import template from './sw-first-run-wizard-paypal-credentials.html.twig';
import './sw-first-run-wizard-paypal-credentials.scss';

export default Shopware.Component.wrapComponentConfig({
    template,

    inject: [
        'SwagPaypalPaymentMethodService',
        'SwagPayPalSettingsService',
    ],

    emits: ['frw-redirect'],

    mixins: [
        Shopware.Mixin.getByName('swag-paypal-notification'),
        Shopware.Mixin.getByName('swag-paypal-settings'),
    ],

    data(): {
        isLoading: boolean;
        asDefault: boolean;
        error: { detail: string; code: string } | null;
    } {
        return {
            isLoading: false,
            asDefault: false,
            error: null,
        };
    },

    computed: {
        buttonConfig() {
            const prev = this.$super('buttonConfig') as { key: string; action: () => Promise<boolean> }[];

            return prev.map((button) => {
                if (button.key === 'next') {
                    button.action = this.onClickNext.bind(this);
                }

                return button;
            });
        },

        hasLiveCredentials() {
            return !!this.settingsStore.get('SwagPayPal.settings.clientId')
                && !!this.settingsStore.get('SwagPayPal.settings.clientSecret');
        },

        hasSandboxCredentials() {
            return !!this.settingsStore.get('SwagPayPal.settings.clientIdSandbox')
                && !!this.settingsStore.get('SwagPayPal.settings.clientSecretSandbox');
        },

        hasCredentials() {
            return (!this.settingsStore.isSandbox && this.hasLiveCredentials)
                || (this.settingsStore.isSandbox && this.hasSandboxCredentials);
        },

        inputsDisabled() {
            return this.isLoading || this.settingsStore.isLoading || this.savingSettings === 'loading';
        },
    },

    watch: {
        'settingsStore.allConfigs': {
            deep: true,
            handler() {
                this.resetError();
            },
        },
    },

    methods: {
        async onClickNext(): Promise<boolean> {
            if (!this.hasCredentials) {
                this.createNotificationError({
                    message: this.$t('swag-paypal-frw-credentials.messageNoCredentials'),
                });

                return true;
            }

            this.isLoading = true;

            const information = (await this.saveSettings())?.null;

            const haveChanged = (!this.settingsStore.isSandbox && information?.liveCredentialsChanged) || (this.settingsStore.isSandbox && information?.sandboxCredentialsChanged);
            let areValid = (!this.settingsStore.isSandbox && information?.liveCredentialsValid) || (this.settingsStore.isSandbox && information?.sandboxCredentialsValid);

            if (!haveChanged) {
                areValid = await this.onTest();
            }

            this.isLoading = false;

            if (!areValid) {
                this.error = {
                    detail: this.$t('swag-paypal-frw-credentials.messageInvalidCredentials'),
                    code: 'ASD',
                };

                return true;
            }

            this.$emit('frw-redirect', 'sw.first.run.wizard.index.plugins');

            if (this.asDefault) {
                try {
                    await this.SwagPaypalPaymentMethodService.setDefaultPaymentForSalesChannel(this.settingsStore.salesChannel);
                } catch {
                    return true;
                }
            }

            return false;
        },

        resetError() {
            this.error = null;
        },

        async onTest() {
            const suffix = this.settingsStore.isSandbox ? 'Sandbox' : '';

            return this.SwagPayPalSettingsService
                .testApiCredentials(
                    this.settingsStore.get(`SwagPayPal.settings.clientId${suffix}`),
                    this.settingsStore.get(`SwagPayPal.settings.clientSecret${suffix}`),
                    this.settingsStore.get(`SwagPayPal.settings.merchantPayerId${suffix}`),
                    this.settingsStore.isSandbox,
                )
                .then((response) => response.valid)
                .catch(() => false);
        },
    },
});
