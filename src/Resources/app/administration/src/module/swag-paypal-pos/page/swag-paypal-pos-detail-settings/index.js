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
        'repositoryFactory',
        'acl',
    ],

    mixins: [
        Shopware.Mixin.getByName('swag-paypal-pos-catch-error'),
        Shopware.Mixin.getByName('placeholder'),
        Shopware.Mixin.getByName('notification'),
    ],

    props: {
        salesChannel: {
            type: Object,
            required: true,
        },
        cloneSalesChannelId: {
            type: String,
            required: false,
            default: null,
        },
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
            previousApiKey: this.salesChannel.extensions.paypalPosSalesChannel.apiKey,
        };
    },

    computed: {
        ...mapPropertyErrors('salesChannel', ['name']),
        ...mapPropertyErrors('swagPaypalPosSalesChannel', ['mediaDomain']),

        salesChannelRepository() {
            return this.repositoryFactory.create('sales_channel');
        },

        optionSyncPrices() {
            return {
                name: this.$tc('swag-paypal-pos.wizard.syncPrices.optionTrueLabel'),
                description: this.$tc('swag-paypal-pos.wizard.syncPrices.optionTrueDescription'),
            };
        },

        optionNotSyncPrices() {
            return {
                name: this.$tc('swag-paypal-pos.wizard.syncPrices.optionFalseLabel'),
                description: this.$tc('swag-paypal-pos.wizard.syncPrices.optionFalseDescription'),
            };
        },

        optionsReplace() {
            return [
                {
                    value: 2,
                    name: this.$tc('swag-paypal-pos.wizard.syncLibrary.optionReplacePermanentlyLabel'),
                    description: this.$tc('swag-paypal-pos.wizard.syncLibrary.optionReplacePermanentlyDescription'),
                }, {
                    value: 1,
                    name: this.$tc('swag-paypal-pos.wizard.syncLibrary.optionReplaceOneTimeLabel'),
                    description: this.$tc('swag-paypal-pos.wizard.syncLibrary.optionReplaceOneTimeDescription'),
                }, {
                    value: 0,
                    name: this.$tc('swag-paypal-pos.wizard.syncLibrary.optionReplaceNotLabel'),
                    description: this.$tc('swag-paypal-pos.wizard.syncLibrary.optionReplaceNotDescription'),
                },
            ];
        },

        swagPaypalPosSalesChannel() {
            return this.salesChannel.extensions.paypalPosSalesChannel;
        },
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

            if (this.swagPaypalPosSalesChannel.apiKey === this.previousApiKey) {
                return this.save();
            }

            return this.SwagPayPalPosSettingApiService
                .validateApiCredentials(this.swagPaypalPosSalesChannel.apiKey, this.salesChannel.id)
                .then(() => {
                    return this.SwagPayPalPosSettingApiService.fetchInformation(this.salesChannel, true);
                })
                .then(this.save)
                .catch((errorResponse) => {
                    this.catchAuthenticationError((errorResponse));
                    this.isLoading = false;
                    this.updateButtons();
                    throw errorResponse;
                });
        },

        save() {
            this.SwagPayPalPosWebhookRegisterService.registerWebhook(this.salesChannel.id)
                .catch(this.catchError.bind(this, 'swag-paypal-pos.messageWebhookRegisterError'));

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
                            this.salesChannel.id,
                        ).catch(this.catchError.bind(this, 'swag-paypal-pos.messageCloneError'));
                    }
                }).catch(() => {
                    this.isLoading = false;
                    this.updateButtons();

                    this.createNotificationError({
                        message: this.$tc('sw-sales-channel.detail.messageSaveError', 0, {
                            name: this.salesChannel.name || this.placeholder(this.salesChannel, 'name'),
                        }),
                    });
                }).finally(() => {
                    if (this.swagPaypalPosSalesChannel.mediaDomain === null) {
                        const expression =
                            `swag_paypal_pos_sales_channel.${this.swagPaypalPosSalesChannel.id}.mediaDomain`;
                        const error = new ShopwareError({ code: 'INVALID_URL' });
                        State.commit('error/addApiError', { expression, error });
                    }
                });
        },

        testCredentials() {
            const apiKey = this.swagPaypalPosSalesChannel.apiKey;

            this.isTestingCredentials = true;
            this.isTestCredentialsSuccessful = false;

            this.SwagPayPalPosSettingApiService.validateApiCredentials(apiKey).then((response) => {
                const credentialsValid = response.credentialsValid;
                this.isTestingCredentials = false;
                this.isTestCredentialsSuccessful = credentialsValid;
            }).catch((errorResponse) => {
                this.catchAuthenticationError(errorResponse);
                this.isTestingCredentials = false;
            });
        },

        catchAuthenticationError(errorResponse) {
            this.catchError('swag-paypal-pos.authentication.messageTestError', errorResponse);

            this.isTestingCredentials = false;
            this.isTestCredentialsSuccessful = false;
        },

        updateButtons() {
            const buttonConfig = [
                {
                    key: 'save',
                    label: this.$tc('global.default.save'),
                    variant: 'primary',
                    action: this.onSave,
                    disabled: false,
                    isLoading: this.isLoading,
                },
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
        },

        changeLanguage() {
            this.$nextTick(() => {
                this.salesChannel.languages.length = 0;
                this.salesChannel.languages.push({
                    id: this.salesChannel.languageId,
                });
                this.$forceUpdate();
            });
        },
    },
});
