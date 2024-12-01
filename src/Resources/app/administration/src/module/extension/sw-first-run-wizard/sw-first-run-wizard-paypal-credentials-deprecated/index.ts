import type * as PayPal from 'src/types';
import template from './sw-first-run-wizard-paypal-credentials.html.twig';
import './sw-first-run-wizard-paypal-credentials.scss';

export default Shopware.Component.wrapComponentConfig({
    template,

    inject: [
        'systemConfigApiService',
        'SwagPaypalPaymentMethodService',
    ],

    mixins: [
        Shopware.Mixin.getByName('swag-paypal-notification'),
        Shopware.Mixin.getByName('swag-paypal-credentials-loader'),
    ],

    data() {
        return {
            config: {} as PayPal.SystemConfig,
            isLoading: false,
            setDefault: false,
        };
    },

    computed: {
        sandboxMode() {
            return this.config['SwagPayPal.settings.sandbox'] || false;
        },

        onboardingUrl() {
            return this.sandboxMode ? this.onboardingUrlSandbox : this.onboardingUrlLive;
        },

        onboardingCallback() {
            return this.sandboxMode ? 'onboardingCallbackSandbox' : 'onboardingCallbackLive';
        },

        buttonConfig() {
            const prev = this.$super('buttonConfig') as { key: string; action: () => Promise<boolean> }[];

            return prev.map((button) => {
                if (button.key === 'next') {
                    button.action = this.onClickNext.bind(this);
                }

                return button;
            });
        },

        credentialsProvided() {
            return (!this.sandboxMode && this.credentialsProvidedLive)
                || (this.sandboxMode && this.credentialsProvidedSandbox);
        },

        credentialsProvidedLive() {
            return !!this.config['SwagPayPal.settings.clientId']
                && !!this.config['SwagPayPal.settings.clientSecret'];
        },

        credentialsProvidedSandbox() {
            return !!this.config['SwagPayPal.settings.clientIdSandbox']
                && !!this.config['SwagPayPal.settings.clientSecretSandbox'];
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.$super('createdComponent');
            this.fetchPayPalConfig();
        },

        onPayPalCredentialsLoadSuccess(clientId: string, clientSecret: string, merchantPayerId: string, sandbox: boolean) {
            this.setConfig(clientId, clientSecret, merchantPayerId, sandbox);
        },

        onPayPalCredentialsLoadFailed(sandbox: boolean) {
            this.setConfig('', '', '', sandbox);
            this.createNotificationError({
                message: this.$tc('swag-paypal-frw-credentials.messageFetchedError'),
                // @ts-expect-error - duration is not defined correctly
                duration: 10000,
            });
        },

        setConfig(clientId: string, clientSecret: string, merchantPayerId: string, sandbox: boolean) {
            const suffix = sandbox ? 'Sandbox' : '';
            this.$set(this.config, `SwagPayPal.settings.clientId${suffix}`, clientId);
            this.$set(this.config, `SwagPayPal.settings.clientSecret${suffix}`, clientSecret);
            this.$set(this.config, `SwagPayPal.settings.merchantPayerId${suffix}`, merchantPayerId);
        },

        async onClickNext(): Promise<boolean> {
            if (!this.credentialsProvided) {
                this.createNotificationError({
                    message: this.$tc('swag-paypal-frw-credentials.messageNoCredentials'),
                });

                return true;
            }

            try {
                // Do not test the credentials if they have been fetched from the PayPal api
                if (!this.isGetCredentialsSuccessful) {
                    await this.testApiCredentials();
                }

                await this.saveConfig();

                this.$emit('frw-redirect', 'sw.first.run.wizard.index.plugins');

                return false;
            } catch {
                return true;
            }
        },

        fetchPayPalConfig() {
            this.isLoading = true;
            return this.systemConfigApiService.getValues('SwagPayPal.settings', null)
                .then((values: PayPal.SystemConfig) => {
                    this.config = values;
                })
                .finally(() => {
                    this.isLoading = false;
                });
        },

        async saveConfig() {
            this.isLoading = true;
            await this.systemConfigApiService.saveValues(this.config, null);

            if (this.setDefault) {
                await this.SwagPaypalPaymentMethodService.setDefaultPaymentForSalesChannel();
            }

            this.isLoading = false;
        },

        async testApiCredentials() {
            this.isLoading = true;

            const sandbox = this.config['SwagPayPal.settings.sandbox'] ?? false;
            const sandboxSetting = sandbox ? 'Sandbox' : '';
            const clientId = this.config[`SwagPayPal.settings.clientId${sandboxSetting}`];
            const clientSecret = this.config[`SwagPayPal.settings.clientSecret${sandboxSetting}`];

            const response = await this.SwagPayPalApiCredentialsService
                .validateApiCredentials(clientId, clientSecret, sandbox)
                .catch((errorResponse: PayPal.ServiceError) => {
                    this.createNotificationFromError({ errorResponse, title: 'swag-paypal.settingForm.messageTestError' });

                    return { credentialsValid: false };
                });

            this.isLoading = false;

            return response.credentialsValid ? Promise.resolve() : Promise.reject();
        },

        onCredentialsChanged() {
            this.isGetCredentialsSuccessful = null;
        },
    },
});
