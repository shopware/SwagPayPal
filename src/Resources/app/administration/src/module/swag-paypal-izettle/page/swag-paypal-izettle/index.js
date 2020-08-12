import template from './swag-paypal-izettle.html.twig';
import './swag-paypal-izettle.scss';

const { Component } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('swag-paypal-izettle', {
    template,

    inject: [
        'salesChannelService',
        'repositoryFactory'
    ],

    mixins: [
        'placeholder'
    ],

    data() {
        return {
            isLoading: false,
            isNewEntity: false,
            previousApiKey: null,
            salesChannel: {},
            cloneSalesChannelId: null,
            buttonConfig: []
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
            this.isNewEntity = false;
            this.loadSalesChannel();
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

        updateButtons(buttonConfig) {
            this.buttonConfig = buttonConfig;
        },

        onButtonClick(action) {
            if (typeof action === 'string') {
                this.redirect(action);
                return;
            }

            if (typeof action !== 'function') {
                return;
            }

            action.call();
        }
    }
});
