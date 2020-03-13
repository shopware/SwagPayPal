import template from './sw-first-run-wizard-paypal-credentials.html.twig';
import './sw-first-run-wizard-paypal-credentials.html.scss';

const { Component, Mixin } = Shopware;

Component.override('sw-first-run-wizard-paypal-credentials', {
    template,

    inject: ['systemConfigApiService'],

    mixins: [
        Mixin.getByName('notification'),
        Mixin.getByName('swag-paypal-credentials-loader')
    ],

    data() {
        return {
            config: {},
            isLoading: false
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
        }
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
            this.setConfig('', '', sandbox);
            this.createNotificationError({
                title: this.$tc('swag-paypal-frw-credentials.titleFetchedError'),
                message: this.$tc('swag-paypal-frw-credentials.messageFetchedError'),
                duration: 10000
            });
        },

        setConfig(clientId, clientSecret, sandbox) {
            if (sandbox) {
                this.$set(this.config, 'SwagPayPal.settings.clientIdSandbox', clientId);
                this.$set(this.config, 'SwagPayPal.settings.clientSecretSandbox', clientSecret);
            } else {
                this.$set(this.config, 'SwagPayPal.settings.clientId', clientId);
                this.$set(this.config, 'SwagPayPal.settings.clientSecret', clientSecret);
            }
        },

        onClickNext() {
            // Skip if no credentials have been provided
            if ((!this.config['SwagPayPal.settings.sandbox'] && (!this.config['SwagPayPal.settings.clientId'] || !this.config['SwagPayPal.settings.clientSecret'])) ||
                (this.config['SwagPayPal.settings.sandbox'] && (!this.config['SwagPayPal.settings.clientIdSandbox'] || !this.config['SwagPayPal.settings.clientSecretSandbox']))) {
                this.createNotificationError({
                    title: this.$tc('swag-paypal-frw-credentials.titleNoCredentials'),
                    message: this.$tc('swag-paypal-frw-credentials.messageNoCredentials')
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

            return this.testApiCredentials().then(result => {
                if (result === 'success') {
                    return this.saveConfig().then(() => {
                        this.$emit('frw-redirect', 'sw.first.run.wizard.index.plugins');

                        return Promise.resolve(false);
                    }).catch(() => {
                        return Promise.resolve(true);
                    });
                }

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
            return this.systemConfigApiService.saveValues(this.config, null).then(() => {
                this.isLoading = false;
            });
        },

        testApiCredentials() {
            this.isLoading = true;

            const clientId = this.config['SwagPayPal.settings.sandbox'] ? this.config['SwagPayPal.settings.clientIdSandbox'] : this.config['SwagPayPal.settings.clientId'];
            const clientSecret = this.config['SwagPayPal.settings.sandbox'] ? this.config['SwagPayPal.settings.clientSecretSandbox'] : this.config['SwagPayPal.settings.clientSecret'];

            return this.SwagPayPalApiCredentialsService.validateApiCredentials(
                clientId,
                clientSecret,
                this.config['SwagPayPal.settings.sandbox']
            ).then((response) => {
                const credentialsValid = response.credentialsValid;

                if (credentialsValid) {
                    this.isLoading = false;
                    return 'success';
                }

                return 'error';
            }).catch((errorResponse) => {
                if (errorResponse.response.data && errorResponse.response.data.errors) {
                    let message = '<ul>';
                    errorResponse.response.data.errors.forEach((error) => {
                        message = `${message}<li>${error.detail}</li>`;
                    });
                    message += '</ul>';
                    this.createNotificationError({
                        title: this.$tc('swag-paypal-frw-credentials.titleTestError'),
                        message: message
                    });
                    this.isLoading = false;
                }

                return 'error';
            });
        },

        onCredentialsChanged() {
            this.isGetCredentialsSuccessful = null;
        }
    }
});
