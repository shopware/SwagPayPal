import { Component, Mixin, State } from 'src/core/shopware';
import template from './swag-paypal.html.twig';

Component.register('swag-paypal', {
    template,

    mixins: [
        Mixin.getByName('notification')
    ],

    inject: ['SwagPayPalWebhookRegisterService', 'SwagPayPalValidateApiCredentialsService'],

    data() {
        return {
            setting: {},
            isLoading: true,
            landingPageTypes: ['Login', 'Billing']
        };
    },

    created() {
        this.createdComponent();
    },

    computed: {
        settingStore() {
            return State.getStore('swag_paypal_setting_general');
        },
        clientCredentialsFilled() {
            return !this.setting.clientId || !this.setting.clientSecret;
        }
    },

    methods: {
        createdComponent() {
            this.settingStore.getList({
                offset: 0,
                limit: 1
            }).then((response) => {
                if (response.items.length > 0) {
                    this.setting = response.items[0];
                } else {
                    this.setting = this.settingStore.create();
                    this.setting.landingPage = this.landingPageTypes[0];
                }
                this.isLoading = false;
            });
        },

        onSave() {
            this.isLoading = true;
            this.setting.save().then(() => {
                this.createNotificationSuccess({
                    title: this.$tc('swag-paypal.settingForm.titleSaveSuccess'),
                    message: this.$tc('swag-paypal.settingForm.messageSaveSuccess')
                });
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
            });
        },

        onTest() {
            this.isLoading = true;
            this.SwagPayPalValidateApiCredentialsService.validateApiCredentials(
                this.setting.clientId,
                this.setting.clientSecret,
                this.setting.sandbox
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
        }
    }
});
