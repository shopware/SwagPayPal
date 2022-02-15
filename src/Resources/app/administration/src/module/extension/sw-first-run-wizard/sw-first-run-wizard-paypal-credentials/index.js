import template from './sw-first-run-wizard-paypal-credentials.html.twig';
import './sw-first-run-wizard-paypal-credentials.scss';

const { Component } = Shopware;

Component.override('sw-first-run-wizard-paypal-credentials', {
    template,

    inject: [
        'systemConfigApiService',
        'SwagPaypalPaymentMethodService',
    ],

    mixins: [
        'notification',
        'swag-paypal-credentials-loader',
    ],

    data() {
        return {
            config: {},
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
            const prev = this.$super('buttonConfig');

            return prev.reduce((acc, button) => {
                if (button.key === 'next') {
                    button.action = this.onClickNext.bind(this);
                }

                return [...acc, button];
            }, []);
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

        onPayPalCredentialsLoadSuccess(clientId, clientSecret, sandbox) {
            this.setConfig(clientId, clientSecret, sandbox);
        },

        onPayPalCredentialsLoadFailed(sandbox) {
            this.setConfig('', '', sandbox, '');
            this.createNotificationError({
                message: this.$tc('swag-paypal-frw-credentials.messageFetchedError'),
                duration: 10000,
            });
        },

        onNewMerchantIdReceived(merchantId, sandbox) {
            if (sandbox) {
                this.$set(this.config, 'SwagPayPal.settings.merchantPayerIdSandbox', merchantId);
            } else {
                this.$set(this.config, 'SwagPayPal.settings.merchantPayerId', merchantId);
            }
        },

        setConfig(clientId, clientSecret, sandbox, merchantId = null) {
            if (sandbox) {
                this.$set(this.config, 'SwagPayPal.settings.clientIdSandbox', clientId);
                this.$set(this.config, 'SwagPayPal.settings.clientSecretSandbox', clientSecret);
                if (merchantId !== null) {
                    this.$set(this.config, 'SwagPayPal.settings.merchantPayerIdSandbox', merchantId);
                }
            } else {
                this.$set(this.config, 'SwagPayPal.settings.clientId', clientId);
                this.$set(this.config, 'SwagPayPal.settings.clientSecret', clientSecret);
                if (merchantId !== null) {
                    this.$set(this.config, 'SwagPayPal.settings.merchantPayerId', merchantId);
                }
            }
        },

        onClickNext() {
            if (!this.credentialsProvided) {
                this.createNotificationError({
                    message: this.$tc('swag-paypal-frw-credentials.messageNoCredentials'),
                });
                return Promise.resolve(true);
            }

            // Do not test the credentials if they have been fetched from the PayPal api
            if (this.isGetCredentialsSuccessful) {
                return this.saveConfig().then(() => {
                    this.$emit('frw-redirect', 'sw.first.run.wizard.index.plugins');

                    return Promise.resolve(false);
                }).catch(() => {
                    return Promise.resolve(true);
                });
            }

            return this.testApiCredentials()
                .then(() => {
                    return this.saveConfig();
                }).then(() => {
                    this.$emit('frw-redirect', 'sw.first.run.wizard.index.plugins');

                    return Promise.resolve(false);
                }).catch(() => {
                    return Promise.resolve(true);
                });
        },

        fetchPayPalConfig() {
            this.isLoading = true;
            return this.systemConfigApiService.getValues('SwagPayPal.settings', null)
                .then(values => {
                    this.config = values;
                })
                .finally(() => {
                    this.isLoading = false;
                });
        },

        saveConfig() {
            this.isLoading = true;
            return this.systemConfigApiService.saveValues(this.config, null)
                .then(() => {
                    if (this.setDefault) {
                        return this.SwagPaypalPaymentMethodService.setDefaultPaymentForSalesChannel();
                    }

                    return Promise.resolve();
                }).then(() => {
                    this.isLoading = false;
                });
        },

        testApiCredentials() {
            this.isLoading = true;

            const sandbox = this.config['SwagPayPal.settings.sandbox'];
            const sandboxSetting = sandbox ? 'Sandbox' : '';
            const clientId = this.config[`SwagPayPal.settings.clientId${sandboxSetting}`];
            const clientSecret = this.config[`SwagPayPal.settings.clientSecret${sandboxSetting}`];

            return this.SwagPayPalApiCredentialsService.validateApiCredentials(clientId, clientSecret, sandbox)
                .then((response) => {
                    const credentialsValid = response.credentialsValid;

                    if (credentialsValid) {
                        this.isLoading = false;
                        return Promise.resolve();
                    }

                    return Promise.reject();
                }).catch((errorResponse) => {
                    if (errorResponse.response.data && errorResponse.response.data.errors) {
                        const message = errorResponse.response.data.errors.map((error) => {
                            return error.detail;
                        }).join(' / ');

                        this.createNotificationError({
                            message: message,
                        });
                        this.isLoading = false;
                    }

                    return Promise.reject();
                });
        },

        onCredentialsChanged() {
            this.isGetCredentialsSuccessful = null;
        },
    },
});
