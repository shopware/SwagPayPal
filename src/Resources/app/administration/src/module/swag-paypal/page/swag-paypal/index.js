import template from './swag-paypal.html.twig';
import './swag-paypal.scss';
import constants from './swag-paypal-consts';

const { Mixin } = Shopware;

Shopware.Component.register('swag-paypal', {
    template,

    inject: ['SwagPayPalWebhookRegisterService', 'SwagPayPalApiCredentialsService'],

    mixins: [
        Mixin.getByName('notification')
    ],

    data() {
        return {
            isLoading: false,
            isTesting: false,
            isSaveSuccessful: false,
            isTestSuccessful: false,
            clientIdFilled: false,
            clientSecretFilled: false,
            config: null,
            clientIdErrorState: null,
            clientSecretErrorState: null,
            ...constants
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    computed: {
        testButtonDisabled() {
            return this.isLoading || !this.clientSecretFilled || !this.clientIdFilled || this.isTesting;
        },

        showSPBCard() {
            return this.config['SwagPayPal.settings.merchantLocation'] === this.MERCHANT_LOCATION_OTHER;
        },

        showPlusCard() {
            return this.config['SwagPayPal.settings.merchantLocation'] === this.MERCHANT_LOCATION_GERMANY;
        }
    },

    watch: {
        config: {
            handler() {
                const defaultConfig = this.$refs.configComponent.allConfigs.null;
                const salesChannelId = this.$refs.configComponent.selectedSalesChannelId;

                if (salesChannelId === null) {
                    this.clientIdFilled = !!this.config['SwagPayPal.settings.clientId'];
                    this.clientSecretFilled = !!this.config['SwagPayPal.settings.clientSecret'];
                } else {
                    this.clientIdFilled = !!this.config['SwagPayPal.settings.clientId']
                        || !!defaultConfig['SwagPayPal.settings.clientId'];
                    this.clientSecretFilled = !!this.config['SwagPayPal.settings.clientSecret']
                        || !!defaultConfig['SwagPayPal.settings.clientSecret'];
                }
            },
            deep: true
        }
    },

    methods: {
        onSave() {
            if (!this.clientIdFilled || !this.clientSecretFilled) {
                this.setErrorStates();
                return;
            }

            this.save();
        },

        save() {
            this.isLoading = true;

            this.$refs.configComponent.save().then((res) => {
                this.isLoading = false;
                this.isSaveSuccessful = true;

                if (res) {
                    this.config = res;
                }

                this.registerWebhook();
            }).catch(() => {
                this.isLoading = false;
            });
        },

        registerWebhook() {
            this.SwagPayPalWebhookRegisterService.registerWebhook(this.$refs.configComponent.selectedSalesChannelId)
                .then((response) => {
                    const result = response.result;

                    if (result === this.WEBHOOK_RESULT_NOTHING) {
                        return;
                    }

                    if (result === this.WEBHOOK_RESULT_CREATED) {
                        this.createNotificationSuccess({
                            title: this.$tc('swag-paypal.settingForm.titleSuccess'),
                            message: this.$tc('swag-paypal.settingForm.messageWebhookCreated')
                        });

                        return;
                    }

                    if (result === this.WEBHOOK_RESULT_UPDATED) {
                        this.createNotificationSuccess({
                            title: this.$tc('swag-paypal.settingForm.titleSuccess'),
                            message: this.$tc('swag-paypal.settingForm.messageWebhookUpdated')
                        });
                    }
                    this.isLoading = false;
                }).catch((errorResponse) => {
                    if (errorResponse.response.data && errorResponse.response.data.errors) {
                        let message = `${this.$tc('swag-paypal.settingForm.messageWebhookError')}<br><br><ul>`;
                        errorResponse.response.data.errors.forEach((error) => {
                            message = `${message}<li>${error.detail}</li>`;
                        });
                        message += '</li>';
                        this.createNotificationError({
                            title: this.$tc('swag-paypal.settingForm.titleError'),
                            message: message
                        });
                    }
                    this.isLoading = false;
                });
        },

        onTest() {
            this.isTesting = true;
            const clientId = this.config['SwagPayPal.settings.clientId'] ||
                this.$refs.configComponent.allConfigs.null['SwagPayPal.settings.clientId'];
            const clientSecret = this.config['SwagPayPal.settings.clientSecret'] ||
                this.$refs.configComponent.allConfigs.null['SwagPayPal.settings.clientSecret'];
            const sandbox = this.config['SwagPayPal.settings.sandbox'] ||
                this.$refs.configComponent.allConfigs.null['SwagPayPal.settings.sandbox'];

            this.SwagPayPalApiCredentialsService.validateApiCredentials(
                clientId,
                clientSecret,
                sandbox
            ).then((response) => {
                const credentialsValid = response.credentialsValid;

                if (credentialsValid) {
                    this.isTesting = false;
                    this.isTestSuccessful = true;
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
                    this.isTesting = false;
                    this.isTestSuccessful = false;
                }
            });
        },

        setErrorStates() {
            if (!this.clientIdFilled) {
                this.clientIdErrorState = {
                    code: 1,
                    detail: this.$tc('swag-paypal.messageNotBlank')
                };
            } else {
                this.clientIdErrorState = null;
            }

            if (!this.clientSecretFilled) {
                this.clientSecretErrorState = {
                    code: 1,
                    detail: this.$tc('swag-paypal.messageNotBlank')
                };
            } else {
                this.clientSecretErrorState = null;
            }
        }
    }
});
