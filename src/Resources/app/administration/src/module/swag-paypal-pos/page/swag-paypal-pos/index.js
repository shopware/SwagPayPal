import template from './swag-paypal-pos.html.twig';
import './swag-paypal-pos.scss';
import { PAYPAL_POS_SALES_CHANNEL_EXTENSION } from '../../../../constant/swag-paypal.constant';

const { Component } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('swag-paypal-pos', {
    template,

    inject: [
        'salesChannelService',
        'repositoryFactory',
    ],

    mixins: [
        Shopware.Mixin.getByName('placeholder'),
    ],

    data() {
        return {
            isLoading: false,
            previousApiKey: null,
            salesChannel: {},
            lastRun: null,
            lastCompleteRun: null,
            cloneSalesChannelId: null,
            buttonConfig: [],
        };
    },

    metaInfo() {
        return {
            title: this.title,
        };
    },

    computed: {
        title() {
            return [
                this.$tc('global.sw-admin-menu.textShopwareAdmin'),
                this.$tc('sw-sales-channel.general.titleMenuItems'),
                this.$tc('swag-paypal-pos.general.moduleTitle'),
            ].reverse().join(' | ');
        },

        paypalPosSalesChannelRepository() {
            return this.repositoryFactory.create('swag_paypal_pos_sales_channel');
        },

        salesChannelRepository() {
            return this.repositoryFactory.create('sales_channel');
        },

        runRepository() {
            return this.repositoryFactory.create('swag_paypal_pos_sales_channel_run');
        },

        salesChannelCriteria() {
            const criteria = new Criteria(1, 500);

            criteria.addAssociation(PAYPAL_POS_SALES_CHANNEL_EXTENSION);
            criteria.addAssociation('countries');
            criteria.addAssociation('currencies');
            criteria.addAssociation('domains');
            criteria.addAssociation('languages');

            return criteria;
        },
    },

    watch: {
        '$route.params.id'() {
            this.loadSalesChannel();
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.loadSalesChannel();
        },

        loadSalesChannel() {
            if (!this.$route.params.id) {
                return Promise.resolve();
            }

            if (this.salesChannel) {
                this.salesChannel = null;
            }

            this.isLoading = true;
            return this.salesChannelRepository
                .get(this.$route.params.id, Shopware.Context.api, this.salesChannelCriteria)
                .then((entity) => {
                    this.salesChannel = entity;
                    this.previousApiKey = entity.extensions.paypalPosSalesChannel.apiKey;
                    this.updateRun();
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
        },

        updateRun() {
            setTimeout(this.updateRun, 20000);
            return this.loadLastRun();
        },

        loadLastRun(needComplete = false) {
            const criteria = new Criteria(1, 1);
            criteria.addFilter(Criteria.equals('salesChannelId', this.salesChannel.id));
            criteria.addFilter(Criteria.not('AND', [Criteria.equals('status', 'in_progress')]));
            criteria.addSorting(Criteria.sort('createdAt', 'DESC'));

            if (needComplete) {
                criteria.addFilter(Criteria.equals('task', 'complete'));
            } else {
                criteria.addAssociation('logs');
            }

            return this.runRepository.search(criteria, Shopware.Context.api).then((result) => {
                if (needComplete) {
                    this.lastCompleteRun = result.first();
                    this.$forceUpdate();
                    return;
                }

                this.lastRun = result.first();
                if (this.lastRun !== null && this.lastRun.task !== 'complete') {
                    this.loadLastRun(true);
                } else {
                    this.lastCompleteRun = this.lastRun;
                }
                this.$forceUpdate();
            });
        },
    },
});
