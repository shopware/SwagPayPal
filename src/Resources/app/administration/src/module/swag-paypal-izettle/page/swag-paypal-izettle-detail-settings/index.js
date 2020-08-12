import template from './swag-paypal-izettle-detail-settings.html.twig';
import './swag-paypal-izettle-detail-settings.scss';

const { Component, Defaults, Context } = Shopware;
const { Criteria } = Shopware.Data;
const { mapPropertyErrors } = Component.getComponentHelper();

Component.register('swag-paypal-izettle-detail-settings', {
    template,

    inject: [
        'SwagPayPalIZettleSettingApiService',
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
            isSaveSuccessful: false,
            isTestingCredentials: false,
            isTestCredentialsSuccessful: false
        };
    },

    computed: {
        ...mapPropertyErrors('salesChannel', ['name']),

        storefrontSalesChannelCriteria() {
            const criteria = new Criteria();
            return criteria.addFilter(Criteria.equals('typeId', Defaults.storefrontSalesChannelTypeId));
        },

        storefrontSalesChannelDomainCriteria() {
            const criteria = new Criteria();
            if (!this.storefrontSalesChannelId) {
                return criteria;
            }
            return criteria.addFilter(Criteria.equals('salesChannelId', this.storefrontSalesChannelId));
        },

        storefrontSalesChannelDomainCurrencyCriteria() {
            const criteria = new Criteria();
            criteria.addAssociation('salesChannels');
            if (!this.storefrontSalesChannelId) {
                return criteria;
            }
            return criteria.addFilter(Criteria.equals('salesChannels.id', this.storefrontSalesChannelId));
        },

        globalDomainRepository() {
            return this.repositoryFactory.create('sales_channel_domain');
        },

        salesChannelRepository() {
            return this.repositoryFactory.create('sales_channel');
        },

        localStorefrontSalesChannelId: {
            get() {
                return this.storefrontSalesChannelId;
            },
            set(storefrontSalesChannelId) {
                this.$emit('update-storefront-sales-channel', storefrontSalesChannelId);
            }
        },

        apiKeyUrl() {
            return this.SwagPayPalIZettleSettingApiService.generateApiUrl();
        }
    },

    mounted() {
        this.mountedComponent();
    },

    methods: {
        mountedComponent() {
            this.updateButtons();
        },

        onStorefrontSelectionChange(storefrontSalesChannelId) {
            this.$emit('update-storefront-sales-channel', storefrontSalesChannelId);
        },

        forceUpdate() {
            this.$forceUpdate();
        },

        onSave() {
            this.isLoading = true;
            this.isSaveSuccessful = false;
            this.updateButtons();

            if (this.salesChannel.extensions.paypalIZettleSalesChannel.apiKey === this.previousApiKey) {
                return this.save();
            }

            return this.SwagPayPalIZettleSettingApiService
                .fetchInformation(this.salesChannel)
                .catch((errorResponse) => {
                    this.catchAuthentificationError((errorResponse));
                    this.isLoading = false;
                    this.updateButtons();
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
                    this.updateButtons();

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
                    this.updateButtons();

                    this.createNotificationError({
                        message: this.$tc('sw-sales-channel.detail.messageSaveError', 0, {
                            name: this.salesChannel.name || this.placeholder(this.salesChannel, 'name')
                        })
                    });
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
            }).catch((errorResponse) => {
                this.catchAuthentificationError(errorResponse);
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

        updateButtons() {
            const buttonConfig = [
                {
                    key: 'save',
                    label: this.$tc('swag-paypal-izettle.detail.save'),
                    variant: 'primary',
                    action: this.onSave,
                    disabled: false,
                    isLoading: this.isLoading
                }
            ];

            this.$emit('buttons-update', buttonConfig);
        }
    }
});
