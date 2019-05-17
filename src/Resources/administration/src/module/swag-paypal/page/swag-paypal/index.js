import { Mixin } from 'src/core/shopware';
import template from './swag-paypal.html.twig';

export default {
    name: 'swag-paypal',

    template,

    mixins: [
        Mixin.getByName('notification')
    ],

    inject: ['SwagPayPalWebhookRegisterService', 'SwagPayPalValidateApiCredentialsService'],

    data() {
        return {
            isLoading: false,
            isSaveSuccessful: false,
            config: {},
            clientIdFilled: false,
            clientSecretFilled: false,
            showValidationErrors: false
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    methods: {
        saveFinish() {
            this.isSaveSuccessful = false;
        },

        onConfigChange(config) {
            this.config = config;

            this.clientIdFilled = !!this.config['SwagPayPal.settings.clientId'];
            this.clientSecretFilled = !!this.config['SwagPayPal.settings.clientSecret'];

            this.showValidationErrors = false;
        },

        onSave() {
            if (!this.clientIdFilled || !this.clientSecretFilled) {
                this.showValidationErrors = true;
                return;
            }

            this.isSaveSuccessful = false;
            this.isLoading = true;
            this.$refs.systemConfig.saveAll().then(() => {
                this.isLoading = false;
                this.isSaveSuccessful = true;

                this.SwagPayPalWebhookRegisterService.registerWebhook().then((response) => {
                    const result = response.result;

                    if (result === 'nothing') {
                        return;
                    }

                    if (result === 'created') {
                        this.createNotificationSuccess({
                            title: this.$tc('swag-paypal.settingForm.titleSaveSuccess'),
                            message: this.$tc('swag-paypal.settingForm.messageWebhookCreated')
                        });

                        return;
                    }

                    if (result === 'updated') {
                        this.createNotificationSuccess({
                            title: this.$tc('swag-paypal.settingForm.titleSaveSuccess'),
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
                            title: this.$tc('swag-paypal.settingForm.titleSaveError'),
                            message: message
                        });
                    }
                    this.isLoading = false;
                });
            }).catch(() => {
                this.isLoading = false;
            });
        },

        onTest() {
            this.isLoading = true;
            this.SwagPayPalValidateApiCredentialsService.validateApiCredentials(
                this.config['SwagPayPal.settings.clientId'],
                this.config['SwagPayPal.settings.clientSecret'],
                this.config['SwagPayPal.settings.sandbox']
            ).then((response) => {
                const credentialsValid = response.credentialsValid;

                if (credentialsValid) {
                    this.createNotificationSuccess({
                        title: this.$tc('swag-paypal.settingForm.titleTestSuccess'),
                        message: this.$tc('swag-paypal.settingForm.messageTestSuccess')
                    });
                    this.isLoading = false;
                }
            }).catch((errorResponse) => {
                if (errorResponse.response.data && errorResponse.response.data.errors) {
                    let message = `${this.$tc('swag-paypal.settingForm.messageTestError')}<br><br><ul>`;
                    errorResponse.response.data.errors.forEach((error) => {
                        message = `${message}<li>${error.detail}</li>`;
                    });
                    message += '</li>';
                    this.createNotificationError({
                        title: this.$tc('swag-paypal.settingForm.titleTestError'),
                        message: message
                    });
                    this.isLoading = false;
                }
            });
        },

        getBind(element, config) {
            if (config !== this.config) {
                this.onConfigChange(config);
            }
            if (this.showValidationErrors) {
                if (element.name === 'SwagPayPal.settings.clientId' && !this.clientIdFilled) {
                    element.config.error = { code: 1, detail: this.$tc('swag-paypal.messageNotBlank') };
                }
                if (element.name === 'SwagPayPal.settings.clientSecret' && !this.clientSecretFilled) {
                    element.config.error = { code: 1, detail: this.$tc('swag-paypal.messageNotBlank') };
                }
            }

            if (element.name === 'SwagPayPal.settings.orderNumberPrefix') {
                element.config.disabled = !this.config['SwagPayPal.settings.sendOrderNumber'];
            }

            return element;
        }
    }
};
