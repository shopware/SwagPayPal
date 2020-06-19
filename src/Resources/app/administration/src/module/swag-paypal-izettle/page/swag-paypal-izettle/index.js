import template from './swag-paypal-izettle.html.twig';
import './swag-paypal-izettle.scss';
import { IZETTLE_SALES_CHANNEL_TYPE_ID } from '../../swag-paypal-izettle-consts';

const { Component, Context, Mixin } = Shopware;
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
        Mixin.getByName('notification'),
        Mixin.getByName('placeholder')
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
            storefrontSalesChannelId: null
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

        createNewSalesChannel() {
            if (Context.api.languageId !== Context.api.systemLanguageId) {
                Context.api.languageId = Context.api.systemLanguageId;
            }

            this.previousApiKey = null;
            this.salesChannel = this.salesChannelRepository.create(Context.api);
            this.salesChannel.typeId = IZETTLE_SALES_CHANNEL_TYPE_ID;

            this.salesChannel.extensions.paypalIZettleSalesChannel
                = this.paypalIZettleSalesChannelRepository.create(Context.api);
            this.salesChannel.extensions.paypalIZettleSalesChannel.apiKey = '';
            this.salesChannel.extensions.paypalIZettleSalesChannel.storefrontSalesChannelId = null;
            this.salesChannel.extensions.paypalIZettleSalesChannel.productStreamId = null;
            this.salesChannel.extensions.paypalIZettleSalesChannel.syncPrices = true;
            this.salesChannel.extensions.paypalIZettleSalesChannel.replace = false;

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

                    if (entity.extensions.paypalIZettleSalesChannel.salesChannelDomainId) {
                        const criteria = new Criteria();
                        criteria.setIds([entity.extensions.paypalIZettleSalesChannel.salesChannelDomainId]);

                        this.globalDomainRepository.search(criteria, Shopware.Context.api).then((result) => {
                            this.storefrontSalesChannelId = result.first().salesChannelId;
                        });
                    }

                    this.isLoading = false;
                });
        },

        updateStorefrontSalesChannel(storefrontSalesChannelId) {
            this.storefrontSalesChannelId = storefrontSalesChannelId;
            this.salesChannelRepository.get(storefrontSalesChannelId, Shopware.Context.api).then((entity) => {
                this.salesChannel.languageId = entity.languageId;
                this.salesChannel.languages.length = 0;
                this.salesChannel.languages.push({
                    id: entity.languageId
                });
                this.salesChannel.paymentMethodId = entity.paymentMethodId;
                this.salesChannel.shippingMethodId = entity.shippingMethodId;
                this.salesChannel.countryId = entity.countryId;
                this.salesChannel.navigationCategoryId = entity.navigationCategoryId;
                this.salesChannel.navigationCategoryVersionId = entity.navigationCategoryVersionId;
                this.salesChannel.customerGroupId = entity.customerGroupId;

                this.salesChannel.name =
                    this.$t('swag-paypal-izettle.wizard.sales-channel.nameDecoration', { name: entity.name });

                this.salesChannel.extensions.paypalIZettleSalesChannel.salesChannelDomainId = null;

                this.$forceUpdate();
            });
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

                    this.$router.push({ name: 'swag.paypal.izettle.detail.base', params: { id: this.salesChannel.id } });
                }).catch(() => {
                    this.isLoading = false;

                    this.createNotificationError({
                        title: this.$tc('sw-sales-channel.detail.titleSaveError'),
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
        },

        onFetchSalesChannelInformation() {
            this.isLoading = true;

            return this.SwagPayPalIZettleSettingApiService.fetchInformation(this.salesChannel).then(() => {
                this.previousApiKey = this.salesChannel.extensions.paypalIZettleSalesChannel.apiKey;
                this.isLoading = false;
            }).catch(this.catchAuthentificationError);
        }
    }
});
