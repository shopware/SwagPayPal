import { ref } from 'vue';
import template from './swag-paypal.html.twig';
import './swag-paypal.scss';
import constants from './swag-paypal-consts';

const { Component, Defaults } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('swag-paypal', {
    template,

    inject: [
        'SwagPayPalApiCredentialsService',
        'SwagPaypalPaymentMethodService',
        'repositoryFactory',
        'acl',
    ],

    mixins: [
        Shopware.Mixin.getByName('notification'),
    ],

    data() {
        return {
            isLoading: false,
            isSaveSuccessful: false,
            isTestSuccessful: false,
            isTestSandboxSuccessful: false,
            clientIdFilled: false,
            clientSecretFilled: false,
            clientIdSandboxFilled: false,
            clientSecretSandboxFilled: false,
            sandboxChecked: false,
            salesChannels: [],
            config: {},
            isSetDefaultPaymentSuccessful: false,
            isSettingDefaultPaymentMethods: false,
            savingDisabled: false,
            messageBlankErrorState: {
                code: 1,
                detail: this.$tc('swag-paypal.messageNotBlank'),
            },
            showCredentials: false,
            allowShowCredentials: true,

            /**
             * @deprecated tag:v10.0.0 - Will be removed, use constants directly
             */
            ...constants,
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle(),
        };
    },

    setup() {
        const configComponent = ref(null);
        return { configComponent };
    },

    computed: {
        /**
         * @deprecated tag:v10.0.0 - Will be removed without replacement.
         */
        showSPBCard() {
            if (!this.configComponent?.allConfigs?.null) {
                return true;
            }

            const merchantLocation = this.config['SwagPayPal.settings.merchantLocation'] ??
                this.configComponent?.allConfigs?.null['SwagPayPal.settings.merchantLocation'];

            const plusEnabled = this.config['SwagPayPal.settings.plusCheckoutEnabled'] ??
                this.configComponent?.allConfigs?.null['SwagPayPal.settings.plusCheckoutEnabled'];

            return merchantLocation !== this.MERCHANT_LOCATION_GERMANY || !plusEnabled;
        },

        /**
         * @deprecated tag:v10.0.0 - Will be removed without replacement.
         */
        showPlusCard() {
            return !this.showSPBCard;
        },

        salesChannelRepository() {
            return this.repositoryFactory.create('sales_channel');
        },

        clientIdErrorState() {
            if (this.isLoading || this.sandboxChecked || this.clientIdFilled) {
                return null;
            }

            return this.messageBlankErrorState;
        },

        clientSecretErrorState() {
            if (this.isLoading || this.sandboxChecked || this.clientSecretFilled) {
                return null;
            }

            return this.messageBlankErrorState;
        },

        clientIdSandboxErrorState() {
            if (this.isLoading || !this.sandboxChecked || this.clientIdSandboxFilled) {
                return null;
            }

            return this.messageBlankErrorState;
        },

        clientSecretSandboxErrorState() {
            if (this.isLoading || !this.sandboxChecked || this.clientSecretSandboxFilled) {
                return null;
            }

            return this.messageBlankErrorState;
        },

        hasError() {
            return (!this.sandboxChecked && !(this.clientIdFilled && this.clientSecretFilled)) ||
                (this.sandboxChecked && !(this.clientIdSandboxFilled && this.clientSecretSandboxFilled));
        },

        salesChannelCriteria() {
            const criteria = new Criteria(1, 500);
            criteria.addFilter(Criteria.equalsAny('typeId', [
                Defaults.storefrontSalesChannelTypeId,
                Defaults.apiSalesChannelTypeId,
            ]));

            return criteria;
        },

        tab() {
            return this.$route.params.tab || 'general';
        },
    },

    watch: {
        config: {
            deep: true,
            handler() {
                const defaultConfig = this.configComponent?.allConfigs?.null;
                const salesChannelId = this.configComponent?.selectedSalesChannelId;

                if (salesChannelId === null) {
                    this.clientIdFilled = !!this.config['SwagPayPal.settings.clientId'];
                    this.clientSecretFilled = !!this.config['SwagPayPal.settings.clientSecret'];
                    this.clientIdSandboxFilled = !!this.config['SwagPayPal.settings.clientIdSandbox'];
                    this.clientSecretSandboxFilled = !!this.config['SwagPayPal.settings.clientSecretSandbox'];
                    this.sandboxChecked = !!this.config['SwagPayPal.settings.sandbox'];
                } else {
                    this.clientIdFilled = !!this.config['SwagPayPal.settings.clientId']
                        || !!defaultConfig?.['SwagPayPal.settings.clientId'];
                    this.clientSecretFilled = !!this.config['SwagPayPal.settings.clientSecret']
                        || !!defaultConfig?.['SwagPayPal.settings.clientSecret'];
                    this.clientIdSandboxFilled = !!this.config['SwagPayPal.settings.clientIdSandbox']
                        || !!defaultConfig?.['SwagPayPal.settings.clientIdSandbox'];
                    this.clientSecretSandboxFilled = !!this.config['SwagPayPal.settings.clientSecretSandbox']
                        || !!defaultConfig?.['SwagPayPal.settings.clientSecretSandbox'];
                    this.sandboxChecked = !!this.config['SwagPayPal.settings.sandbox']
                        || !!defaultConfig?.['SwagPayPal.settings.sandbox'];
                }
            },
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            if (!this.$route.params.tab) {
                this.$router.push({ name: 'swag.paypal.index', params: { tab: 'general' } });
            }

            this.isLoading = true;

            this.salesChannelRepository.search(this.salesChannelCriteria, Shopware.Context.api).then(res => {
                res.add({
                    id: null,
                    translated: {
                        name: this.$tc('sw-sales-channel-switch.labelDefaultOption'),
                    },
                });

                this.salesChannels = res;
            }).finally(() => {
                this.isLoading = false;
            });
        },

        onSave() {
            if (this.hasError) {
                return;
            }

            this.save();
        },

        onChangeLoading(state) {
            this.isLoading = state;
        },

        save() {
            this.isLoading = true;

            this.configComponent?.save().then((response) => {
                this.isSaveSuccessful = true;

                if (response.payPalWebhookErrors) {
                    const errorMessage = this.$tc('swag-paypal.settingForm.messageWebhookError');
                    response.payPalWebhookErrors.forEach((error) => {
                        this.createNotificationError({
                            message: `${errorMessage}<br><br><ul><li>${error}</li></ul>`,
                        });
                    });
                }
            }).finally(() => {
                this.isLoading = false;
            });
        },

        onSetPaymentMethodDefault() {
            this.isSettingDefaultPaymentMethods = true;

            this.SwagPaypalPaymentMethodService.setDefaultPaymentForSalesChannel(
                this.configComponent?.selectedSalesChannelId,
            ).then(() => {
                this.isSettingDefaultPaymentMethods = false;
                this.isSetDefaultPaymentSuccessful = true;
            });
        },

        preventSave(mode) {
            this.savingDisabled = !!mode;
        },

        onChangeCredentialsVisibility(visibility) {
            this.showCredentials = visibility;
        },
    },
});
