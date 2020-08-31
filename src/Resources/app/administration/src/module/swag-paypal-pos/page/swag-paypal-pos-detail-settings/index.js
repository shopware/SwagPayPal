import template from './swag-paypal-pos-detail-settings.html.twig';
import './swag-paypal-pos-detail-settings.scss';

const { Component, Context, State } = Shopware;
const { mapPropertyErrors } = Component.getComponentHelper();
const { ShopwareError } = Shopware.Classes;


Component.register('swag-paypal-pos-detail-settings', {
    template,

    inject: [
        'SwagPayPalPosApiService',
        'SwagPayPalPosSettingApiService',
        'SwagPayPalPosWebhookRegisterService',
        'salesChannelService',
        'repositoryFactory'
    ],

    mixins: [
        'placeholder',
        'notification'
    ],

    props: {
        salesChannel: {
            type: Object,
            required: true
        },
        cloneSalesChannelId: {
            type: String,
            required: false
        }
    },

    data() {
        return {
            isLoading: false,
            showDeleteModal: false,
            showResetModal: false,
            isSaveSuccessful: false,
            isTestingCredentials: false,
            isTestCredentialsSuccessful: false,
            apiKeyUrl: this.SwagPayPalPosSettingApiService.generateApiUrl(),
            previousApiKey: this.salesChannel.extensions.paypalPosSalesChannel.apiKey
        };
    },

    computed: {
        ...mapPropertyErrors('salesChannel', ['name']),
        ...mapPropertyErrors('swagPaypalIzettleSalesChannel', ['mediaDomain']),

        salesChannelRepository() {
            return this.repositoryFactory.create('sales_channel');
        },

        optionSyncPrices() {
            return {
                name: this.$tc('swag-paypal-pos.wizard.syncPrices.optionTrueLabel'),
                description: this.$tc('swag-paypal-pos.wizard.syncPrices.optionTrueDescription')
            };
        },

        optionNotSyncPrices() {
            return {
                name: this.$tc('swag-paypal-pos.wizard.syncPrices.optionFalseLabel'),
                description: this.$tc('swag-paypal-pos.wizard.syncPrices.optionFalseDescription')
            };
        },

        optionReplace() {
            return {
                name: this.$tc('swag-paypal-pos.wizard.syncLibrary.optionReplaceLabel'),
                description: this.$tc('swag-paypal-pos.wizard.syncLibrary.optionReplaceDescription')
            };
        },

        optionAdd() {
            return {
                name: this.$tc('swag-paypal-pos.wizard.syncLibrary.optionAddLabel'),
                description: this.$tc('swag-paypal-pos.wizard.syncLibrary.optionAddDescription')
            };
        },

        swagPaypalIzettleSalesChannel() {
            return this.salesChannel.extensions.paypalPosSalesChannel;
        }
    },

    mounted() {
        this.mountedComponent();
    },

    methods: {
        mountedComponent() {
            this.updateButtons();
        },

        forceUpdate() {
            this.$forceUpdate();
        },

        onSave() {
            this.isLoading = true;
            this.isSaveSuccessful = false;
            this.updateButtons();

            if (this.swagPaypalIzettleSalesChannel.apiKey === this.previousApiKey) {
                return this.save();
            }

            return this.SwagPayPalPosSettingApiService
                .fetchInformation(this.salesChannel)
                .then(this.save)
                .catch((errorResponse) => {
                    this.catchAuthentificationError((errorResponse));
                    this.isLoading = false;
                    this.updateButtons();
                    throw errorResponse;
                });
        },

        save() {
            this.SwagPayPalPosWebhookRegisterService.registerWebhook(this.salesChannel.id)
                .catch((errorResponse) => {
                    if (errorResponse.response.data && errorResponse.response.data.errors) {
                        const message = errorResponse.response.data.errors.map((error) => {
                            return error.detail;
                        }).join(' / ');

                        this.createNotificationError({
                            title: this.$tc('global.default.error'),
                            message: `${this.$tc('swag-paypal-pos.messageWebhookRegisterError')}: ${message}`
                        });
                    }
                });

            return this.salesChannelRepository
                .save(this.salesChannel, Context.api)
                .then(() => {
                    this.isLoading = false;
                    this.isSaveSuccessful = true;
                    this.updateButtons();

                    this.$emit('load-sales-channel');
                    this.$root.$emit('sales-channel-change');

                    if (this.cloneSalesChannelId !== null) {
                        this.SwagPayPalPosSettingApiService.cloneProductVisibility(
                            this.cloneSalesChannelId,
                            this.salesChannel.id
                        ).catch((errorResponse) => {
                            if (errorResponse.response.data && errorResponse.response.data.errors) {
                                this.createNotificationError({
                                    title: this.$tc('global.default.error'),
                                    message: this.$tc('swag-paypal-pos.messageCloneError')
                                });
                            }
                        });
                    }
                }).catch(() => {
                    this.isLoading = false;
                    this.updateButtons();

                    this.createNotificationError({
                        message: this.$tc('sw-sales-channel.detail.messageSaveError', 0, {
                            name: this.salesChannel.name || this.placeholder(this.salesChannel, 'name')
                        })
                    });
                }).finally(() => {
                    if (this.swagPaypalIzettleSalesChannel.mediaDomain === null) {
                        const expression =
                            `swag_paypal_pos_sales_channel.${this.swagPaypalIzettleSalesChannel.id}.mediaDomain`;
                        const error = new ShopwareError({ code: 'INVALID_URL' });
                        State.commit('error/addApiError', { expression, error });
                    }
                });
        },

        onTestCredentials() {
            const apiKey = this.swagPaypalIzettleSalesChannel.apiKey;

            this.isTestingCredentials = true;
            this.isTestCredentialsSuccessful = false;

            this.SwagPayPalPosSettingApiService.validateApiCredentials(apiKey).then((response) => {
                const credentialsValid = response.credentialsValid;
                this.isTestingCredentials = false;
                this.isTestCredentialsSuccessful = credentialsValid;
            }).catch((errorResponse) => {
                this.catchAuthentificationError(errorResponse);
                this.isTestingCredentials = false;
            });
        },

        catchAuthentificationError(errorResponse) {
            if (errorResponse.response.data && errorResponse.response.data.errors) {
                let message = `<b>${this.$tc('swag-paypal-pos.authentification.messageTestError')}</b> `;
                message += errorResponse.response.data.errors.map((error) => {
                    return error.detail;
                }).join(' / ');

                this.createNotificationError({
                    title: this.$tc('global.default.error'),
                    message
                });

                this.isTestingCredentials = false;
                this.isTestCredentialsSuccessful = false;
            }
        },

        updateButtons() {
            const buttonConfig = [
                {
                    key: 'save',
                    label: this.$tc('swag-paypal-pos.detail.save'),
                    variant: 'primary',
                    action: this.onSave,
                    disabled: false,
                    isLoading: this.isLoading
                }
            ];

            this.$emit('buttons-update', buttonConfig);
        },

        onConfirmDelete() {
            this.showDeleteModal = false;

            this.$nextTick(() => {
                this.deleteSalesChannel(this.salesChannel.id);
                this.$router.push({ name: 'sw.dashboard.index' });
            });
        },

        deleteSalesChannel(salesChannelId) {
            return this.SwagPayPalPosWebhookRegisterService.unregisterWebhook(salesChannelId).finally(() => {
                return this.salesChannelRepository.delete(salesChannelId, Shopware.Context.api).then(() => {
                    this.$root.$emit('sales-channel-change');
                });
            });
        },

        onConfirmReset() {
            this.showResetModal = false;

            this.$nextTick(() => {
                this.SwagPayPalPosApiService.resetSync(this.salesChannel.id).then(() => {
                    this.$router.push({ name: 'swag.paypal.pos.detail.overview', params: { id: this.salesChannel.id } });
                });
            });
        }
    }
});
