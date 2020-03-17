import template from './swag-paypal-credentials.html.twig';

const { Component, Mixin } = Shopware;

Component.register('sw-paypal-credentials', {
    template,

    name: 'SwagPaypalCredentials',

    mixins: [
        Mixin.getByName('notification'),
        Mixin.getByName('swag-paypal-credentials-loader')
    ],

    props: {
        actualConfigData: {
            type: Object,
            required: true
        },
        allConfigs: {
            type: Object,
            required: true
        },
        selectedSalesChannelId: {
            required: true
        },
        clientIdErrorState: {
            required: true
        },
        clientSecretErrorState: {
            required: true
        },
        clientIdSandboxErrorState: {
            required: true
        },
        clientSecretSandboxErrorState: {
            required: true
        },
        clientIdFilled: {
            type: Boolean,
            required: true
        },
        clientSecretFilled: {
            type: Boolean,
            required: true
        },
        clientIdSandboxFilled: {
            type: Boolean,
            required: true
        },
        clientSecretSandboxFilled: {
            type: Boolean,
            required: true
        },
        isLoading: {
            type: Boolean,
            required: true
        }
    },

    data() {
        return {
            isTestingLive: false,
            isTestingSandbox: false,
            isTestLiveSuccessful: false,
            isTestSandboxSuccessful: false
        };
    },

    computed: {
        testLiveButtonDisabled() {
            return this.isLoading || !this.clientSecretFilled || !this.clientIdFilled || this.isTestingLive;
        },

        testSandboxButtonDisabled() {
            return this.isLoading || !this.clientSecretSandboxFilled || !this.clientIdSandboxFilled || this.isTestingSandbox;
        }
    },

    methods: {
        checkTextFieldInheritance(value) {
            if (typeof value !== 'string') {
                return true;
            }

            return value.length <= 0;
        },

        checkBoolFieldInheritance(value) {
            return typeof value !== 'boolean';
        },

        onTest(sandbox) {
            let clientId;
            let clientSecret;

            if (sandbox) {
                this.isTestingSandbox = true;
            } else {
                this.isTestingLive = true;
            }

            if (sandbox) {
                clientId = this.actualConfigData['SwagPayPal.settings.clientIdSandbox'] ||
                    this.allConfigs.null['SwagPayPal.settings.clientIdSandbox'];
                clientSecret = this.actualConfigData['SwagPayPal.settings.clientSecretSandbox'] ||
                    this.allConfigs.null['SwagPayPal.settings.clientSecretSandbox'];
            } else {
                clientId = this.actualConfigData['SwagPayPal.settings.clientId'] ||
                    this.allConfigs.null['SwagPayPal.settings.clientId'];
                clientSecret = this.actualConfigData['SwagPayPal.settings.clientSecret'] ||
                    this.allConfigs.null['SwagPayPal.settings.clientSecret'];
            }

            this.SwagPayPalApiCredentialsService.validateApiCredentials(
                clientId,
                clientSecret,
                sandbox
            ).then((response) => {
                const credentialsValid = response.credentialsValid;

                if (credentialsValid) {
                    if (sandbox) {
                        this.isTestingSandbox = false;
                        this.isTestSandboxSuccessful = true;
                    } else {
                        this.isTestingLive = false;
                        this.isTestLiveSuccessful = true;
                    }
                }
            }).catch((errorResponse) => {
                if (errorResponse.response.data && errorResponse.response.data.errors) {
                    let message = `${this.$tc('swag-paypal.settingForm.messageTestError')}<br><br><ul>`;
                    errorResponse.response.data.errors.forEach((error) => {
                        message = `${message}<li>${error.detail}</li>`;
                    });

                    message += '</li>';
                    this.createNotificationError({
                        title: this.$tc('swag-paypal.settingForm.titleError'),
                        message: message
                    });

                    if (sandbox) {
                        this.isTestingSandbox = false;
                        this.isTestSandboxSuccessful = false;
                    } else {
                        this.isTestingLive = false;
                        this.isTestLiveSuccessful = false;
                    }
                }
            });
        },

        onPayPalCredentialsLoadSuccess(clientId, clientSecret, sandbox) {
            if (sandbox) {
                this.$set(this.actualConfigData, 'SwagPayPal.settings.clientIdSandbox', clientId);
                this.$set(this.actualConfigData, 'SwagPayPal.settings.clientSecretSandbox', clientSecret);
            } else {
                this.$set(this.actualConfigData, 'SwagPayPal.settings.clientId', clientId);
                this.$set(this.actualConfigData, 'SwagPayPal.settings.clientSecret', clientSecret);
            }
        },

        onPayPalCredentialsLoadFailed(sandbox) {
            if (sandbox) {
                this.$set(this.actualConfigData, 'SwagPayPal.settings.clientIdSandbox', '');
                this.$set(this.actualConfigData, 'SwagPayPal.settings.clientSecretSandbox', '');
            } else {
                this.$set(this.actualConfigData, 'SwagPayPal.settings.clientId', '');
                this.$set(this.actualConfigData, 'SwagPayPal.settings.clientSecret', '');
            }
            this.createNotificationError({
                title: this.$tc('swag-paypal.settingForm.credentials.button.titleFetchedError'),
                message: this.$tc('swag-paypal.settingForm.credentials.button.messageFetchedError'),
                duration: 10000
            });
        }
    }
});
