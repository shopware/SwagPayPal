import template from './swag-paypal.html.twig';
import './swag-paypal.scss';
import constants from './swag-paypal-consts';

const { Mixin } = Shopware;

export default {
    name: 'swag-paypal',

    template,

    mixins: [
        Mixin.getByName('notification')
    ],

    inject: ['SwagPayPalWebhookRegisterService', 'SwagPayPalApiCredentialsService'],

    data() {
        return {
            isLoading: false,
            isSaveSuccessful: false,
            clientIdFilled: false,
            clientSecretFilled: false,
            intentOptions: [
                {
                    id: 'sale',
                    name: this.$tc('swag-paypal.settingForm.behaviour.intent.sale')
                },
                {
                    id: 'authorize',
                    name: this.$tc('swag-paypal.settingForm.behaviour.intent.authorize')
                },
                {
                    id: 'order',
                    name: this.$tc('swag-paypal.settingForm.behaviour.intent.order')
                }
            ],
            landingPageOptions: [
                {
                    id: 'Login',
                    name: this.$tc('swag-paypal.settingForm.behaviour.landingPage.options.Login')
                },
                {
                    id: 'Billing',
                    name: this.$tc('swag-paypal.settingForm.behaviour.landingPage.options.Billing')
                }
            ],
            buttonColorOptions: [
                {
                    id: 'blue',
                    name: this.$tc('swag-paypal.settingForm.express.ecsButtonColor.options.blue')
                },
                {
                    id: 'black',
                    name: this.$tc('swag-paypal.settingForm.express.ecsButtonColor.options.black')
                },
                {
                    id: 'gold',
                    name: this.$tc('swag-paypal.settingForm.express.ecsButtonColor.options.gold')
                },
                {
                    id: 'silver',
                    name: this.$tc('swag-paypal.settingForm.express.ecsButtonColor.options.silver')
                }
            ],
            buttonShapeOptions: [
                {
                    id: 'pill',
                    name: this.$tc('swag-paypal.settingForm.express.ecsButtonShape.options.pill')
                },
                {
                    id: 'rect',
                    name: this.$tc('swag-paypal.settingForm.express.ecsButtonShape.options.rect')
                }
            ],
            config: null,
            clientIdErrorState: null,
            clientSecretErrorState: null,
            ...constants
        };
    },

    computed: {
        clientIdError: {
            get() {
                return this.clientIdErrorState;
            },
            set(value) {
                this.clientIdErrorState = value;
            }
        },

        clientSecretError: {
            get() {
                return this.clientSecretErrorState;
            },
            set(value) {
                this.clientSecretErrorState = value;
            }
        }
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
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
        checkTextFieldInheritance(value) {
            if (typeof value !== 'string') {
                return true;
            }

            return value.length <= 0;
        },

        checkBoolFieldInheritance(value) {
            if (typeof value !== 'boolean') {
                return true;
            }

            return false;
        },

        getInheritTextValue(key) {
            const salesChannelId = this.$refs.configComponent.selectedSalesChannelId;
            const res = salesChannelId == null ? null : this.$refs.configComponent.allConfigs.null[key];

            if (salesChannelId !== null && (typeof res === 'undefined' || res === null)) {
                return '';
            }

            return res;
        },

        onSave() {
            if (!this.clientIdFilled || !this.clientSecretFilled) {
                this.setErrorStates();
                return;
            }

            this._save();
        },

        _save() {
            this.isLoading = true;

            this.$refs.configComponent.save().then((res) => {
                this.isLoading = false;
                this.isSaveSuccessful = true;

                if (res) {
                    this.config = res;
                }

                this.createNotificationSuccess({
                    title: this.$tc('swag-paypal.settingForm.titleSuccess'),
                    message: this.$tc('swag-paypal.settingForm.messageSaveSuccess')
                });

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
            this.isLoading = true;
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
                    this.createNotificationSuccess({
                        title: this.$tc('swag-paypal.settingForm.titleSuccess'),
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
                        title: this.$tc('swag-paypal.settingForm.titleError'),
                        message: message
                    });
                    this.isLoading = false;
                }
            });
        },

        setErrorStates() {
            if (!this.clientIdFilled) {
                this.clientIdError = {
                    code: 1,
                    detail: this.$tc('swag-paypal.messageNotBlank')
                };
            } else {
                this.clientIdError = null;
            }

            if (!this.clientSecretFilled) {
                this.clientSecretError = {
                    code: 1,
                    detail: this.$tc('swag-paypal.messageNotBlank')
                };
            } else {
                this.clientSecretError = null;
            }
        }
    }
};
