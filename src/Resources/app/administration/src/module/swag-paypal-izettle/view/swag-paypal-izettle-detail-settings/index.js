import template from './swag-paypal-izettle-detail-settings.html.twig';
import './swag-paypal-izettle-detail-settings.scss';

const { Component, Mixin, Defaults } = Shopware;
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
        Mixin.getByName('placeholder')
    ],

    props: {
        salesChannel: {
            type: Object,
            required: true
        },
        isLoading: {
            type: Boolean,
            default: false
        },
        storefrontSalesChannelId: {
            type: String,
            required: false
        },
        isTestingCredentials: {
            type: Boolean,
            required: false
        },
        isTestCredentialsSuccessful: {
            type: Boolean,
            required: false
        }
    },

    data() {
        return {
            showDeleteModal: false
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

    methods: {
        onStorefrontSelectionChange(storefrontSalesChannelId) {
            this.$emit('update-storefront-sales-channel', storefrontSalesChannelId);
        },

        forceUpdate() {
            this.$forceUpdate();
        },

        onTestCredentials() {
            this.$emit('test-credentials');
        }
    }
});
