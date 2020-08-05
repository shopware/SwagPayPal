import template from './swag-paypal-izettle.html.twig';
import './swag-paypal-izettle.scss';
import { IZETTLE_SALES_CHANNEL_TYPE_ID } from '../../swag-paypal-izettle-consts';

const { Component, Context } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('swag-paypal-izettle', {
    template,

    inject: [
        'SwagPayPalIZettleApiService',
        'SwagPayPalIZettleSettingApiService',
        'salesChannelService',
        'repositoryFactory'
    ],

    mixins: [
        'notification',
        'placeholder'
    ],

    data() {
        return {
            isLoading: false,
            isSaveSuccessful: false,
            isCleaningLog: false,
            isCleanLogSuccessful: false,
            isTestingCredentials: false,
            isTestCredentialsSuccessful: false,
            isNewEntity: false,
            previousApiKey: null,
            showWizard: false,
            salesChannel: {},
            cloneSalesChannelId: null
        };
    },

    computed: {
        paypalIZettleSalesChannelRepository() {
            return this.repositoryFactory.create('swag_paypal_izettle_sales_channel');
        },

        salesChannelRepository() {
            return this.repositoryFactory.create('sales_channel');
        },

        globalDomainRepository() {
            return this.repositoryFactory.create('sales_channel_domain');
        },

        salesChannelCriteria() {
            const criteria = new Criteria();

            criteria.addAssociation('countries');
            criteria.addAssociation('currencies');
            criteria.addAssociation('domains');
            criteria.addAssociation('languages');

            return criteria;
        },

        showLogCleanAction() {
            return this.$route.path.indexOf('log') !== -1 && !this.isNewEntity;
        }
    },

    watch: {
        '$route.params.id'() {
            this.createdComponent();
        }
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            if (this.$route.path.indexOf('wizard') !== -1) {
                this.isNewEntity = true;
                this.createNewSalesChannel();
                this.showWizard = true;
            } else {
                this.isNewEntity = false;
                this.loadSalesChannel();
            }
        },

        // ToDo PPI-22@M.Janz - Tidy up those functions

        createNewSalesChannel() {
            if (Context.api.languageId !== Context.api.systemLanguageId) {
                Context.api.languageId = Context.api.systemLanguageId;
            }

            this.previousApiKey = null;
            this.salesChannel = this.salesChannelRepository.create(Context.api);
            this.salesChannel.typeId = IZETTLE_SALES_CHANNEL_TYPE_ID;

            this.salesChannel.extensions.paypalIZettleSalesChannel
                = this.paypalIZettleSalesChannelRepository.create(Context.api);

            Object.assign(
                this.salesChannel.extensions.paypalIZettleSalesChannel,
                {
                    mediaDomain: 'https://example.com',
                    apiKey: '',
                    imageDomain: '',
                    productStreamId: null,
                    syncPrices: true,
                    replace: false
                }
            );

            this.salesChannelService.generateKey().then((response) => {
                this.salesChannel.accessKey = response.accessKey;
            }).catch(() => {
                this.createNotificationError({
                    title: this.$tc('sw-sales-channel.detail.titleAPIError'),
                    message: this.$tc('sw-sales-channel.detail.messageAPIError')
                });
            });
        },

        loadSalesChannel() {
            if (!this.$route.params.id) {
                return;
            }

            if (this.salesChannel) {
                this.salesChannel = null;
            }

            this.isLoading = true;
            this.salesChannelRepository
                .get(this.$route.params.id, Shopware.Context.api, this.salesChannelCriteria)
                .then((entity) => {
                    this.salesChannel = entity;
                    this.previousApiKey = entity.extensions.paypalIZettleSalesChannel.apiKey;
                    this.isLoading = false;
                });
        },

        updateCloneSalesChannel(cloneSalesChannelId) {
            this.cloneSalesChannelId = cloneSalesChannelId;
        },

        cancelWizard() {
            this.showWizard = false;

            this.$nextTick(() => {
                this.$router.push({ name: 'sw.dashboard.index' });
            });
        },

        onSave() {
            this.isLoading = true;
            this.isSaveSuccessful = false;

            if (this.salesChannel.extensions.paypalIZettleSalesChannel.apiKey === this.previousApiKey) {
                return this.save();
            }

            return this.SwagPayPalIZettleSettingApiService
                .fetchInformation(this.salesChannel)
                .catch((errorResponse) => {
                    this.catchAuthentificationError((errorResponse));
                    this.isLoading = false;
                    throw errorResponse;
                })
                .then(this.save);
        },

        save() {
            return this.salesChannelRepository
                .save(this.salesChannel, Context.api)
                .then(() => {
                    this.isLoading = false;
                    this.isSaveSuccessful = true;
                    this.isNewEntity = false;
                    this.showWizard = false;

                    this.$root.$emit('sales-channel-change');
                    this.loadSalesChannel();

                    if (this.cloneSalesChannelId !== null) {
                        this.SwagPayPalIZettleSettingApiService.cloneProductVisibility(
                            this.cloneSalesChannelId,
                            this.salesChannel.id
                        ).catch((errorResponse) => {
                            if (errorResponse.response.data && errorResponse.response.data.errors) {
                                this.createNotificationError({
                                    title: this.$tc('global.default.error'),
                                    message: this.$tc('swag-paypal-izettle.messageCloneError')
                                });
                            }
                        });
                    }

                    this.$router.push({ name: 'swag.paypal.izettle.detail.base', params: { id: this.salesChannel.id } });
                }).catch(() => {
                    this.isLoading = false;

                    this.createNotificationError({
                        message: this.$tc('sw-sales-channel.detail.messageSaveError', 0, {
                            name: this.salesChannel.name || this.placeholder(this.salesChannel, 'name')
                        })
                    });
                });
        },

        onCleanLog() {
            this.isCleaningLog = true;
            this.isCleanLogSuccessful = false;

            this.SwagPayPalIZettleApiService.startLogCleanup(this.salesChannel.id).then(() => {
                this.isCleaningLog = false;
                this.isCleanLogSuccessful = true;
            }).catch((errorResponse) => {
                if (errorResponse.response.data && errorResponse.response.data.errors) {
                    let message = '';
                    message += errorResponse.response.data.errors.map((error) => {
                        return error.detail;
                    }).join(' / ');

                    this.createNotificationError({
                        title: this.$tc('global.default.error'),
                        message
                    });

                    this.isCleaningLog = false;
                    this.isCleanLogSuccessful = false;
                }
            });
        },

        onTestCredentials() {
            const apiKey = this.salesChannel.extensions.paypalIZettleSalesChannel.apiKey;

            this.isTestingCredentials = true;
            this.isTestCredentialsSuccessful = false;

            this.SwagPayPalIZettleSettingApiService.validateApiCredentials(apiKey).then((response) => {
                const credentialsValid = response.credentialsValid;
                this.isTestingCredentials = false;
                this.isTestCredentialsSuccessful = credentialsValid;
            }).catch(() => {
                this.catchAuthentificationError();
                this.isTestingCredentials = false;
            });
        },

        catchAuthentificationError(errorResponse) {
            if (errorResponse.response.data && errorResponse.response.data.errors) {
                let message = `<b>${this.$tc('swag-paypal-izettle.authentification.messageTestError')}</b> `;
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
        }
    }
});
