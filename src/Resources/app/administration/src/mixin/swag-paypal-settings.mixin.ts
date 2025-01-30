import type * as PayPal from 'SwagPayPal/types';

export default Shopware.Mixin.register('swag-paypal-settings', Shopware.Component.wrapComponentConfig({
    inject: [
        'systemConfigApiService',
        'SwagPayPalSettingsService',
    ],

    mixins: [
        Shopware.Mixin.getByName('swag-paypal-notification'),
    ],

    data(): {
        savingSettings: 'none' | 'loading' | 'success';
    } {
        return {
            savingSettings: 'none',
        };
    },

    computed: {
        settingsStore() {
            return Shopware.Store.get('swagPayPalSettings');
        },
    },

    watch: {
        'settingsStore.salesChannel': {
            immediate: true,
            handler(salesChannel: string | null) {
                this.fetchSettings(salesChannel);
            },
        },
    },

    beforeCreate() {
        // computed properties aren't ready yet
        Shopware.Store.get('swagPayPalSettings').$reset();
    },

    methods: {
        async fetchSettings(salesChannel: string | null): Promise<void> {
            if (this.settingsStore.hasConfig(salesChannel)) {
                return;
            }

            const config = await this.systemConfigApiService.getValues('SwagPayPal.settings', salesChannel as null) as PayPal.SystemConfig;

            this.settingsStore.setConfig(salesChannel, config || {});
        },

        async saveSettings(): Promise<Record<string, PayPal.Setting<'settings_information'>> | void> {
            this.savingSettings = 'loading';

            return this.SwagPayPalSettingsService.save(this.settingsStore.allConfigs)
                .then((response) => {
                    Object.entries(response).forEach(([salesChannel, information]) => {
                        this.handleSettingsSaveInformation(salesChannel, information);
                    });

                    this.savingSettings = 'success';

                    setTimeout(() => { this.savingSettings = 'none'; }, 5000);

                    return response;
                })
                .catch((error: PayPal.ServiceError) => {
                    this.createNotificationError({
                        title: this.$t('swag-paypal.notifications.save.title'),
                        message: this.$t('swag-paypal.notifications.save.errorMessage', {
                            message: this.createMessageFromError(error),
                        }),
                    });

                    this.savingSettings = 'none';
                });
        },

        handleSettingsSaveInformation(salesChannel: string, information: PayPal.Setting<'settings_information'>) {
            if (information.sandboxCredentialsChanged || information.liveCredentialsChanged) {
                Shopware.Store.get('swagPayPalMerchantInformation').delete(salesChannel);
            }

            information.webhookErrors.forEach((message) => {
                this.createNotificationWarning({
                    title: this.$t('swag-paypal.notifications.save.title'),
                    message: this.$t('swag-paypal.notifications.save.webhookMessage', { message }),
                });
            });
        },
    },
}));
