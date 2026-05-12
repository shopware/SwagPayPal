import template from './swag-paypal-pos-detail-logs.html.twig';
import './swag-paypal-pos-detail-logs.scss';

const { Component, Context } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('swag-paypal-pos-detail-logs', {
    template,

    inject: [
        'SwagPayPalPosApiService',
        'repositoryFactory',
    ],

    mixins: [
        Shopware.Mixin.getByName('notification'),
        Shopware.Mixin.getByName('swag-paypal-pos-log-label'),
        Shopware.Mixin.getByName('listing'),
    ],

    props: {
        runId: {
            type: String,
            required: true,
        },
    },

    data() {
        return {
            logs: [],
            limit: 10,
            sortBy: 'createdAt',
            sortDirection: 'DESC',
            loadingLogs: false,
            isLoading: false,
            disableRouteParams: true,
        };
    },

    computed: {
        logRepository() {
            return this.repositoryFactory.create('swag_paypal_pos_sales_channel_run_log');
        },

        columns() {
            return [{
                property: 'date',
                dataIndex: 'createdAt',
                label: 'swag-paypal-pos.detail.syncedProducts.columns.date',
                width: '140px',
                sortable: true,
            }, {
                property: 'state',
                dataIndex: 'level',
                label: 'swag-paypal-pos.detail.syncedProducts.columns.state',
                width: '120px',
                sortable: true,
            }, {
                property: 'message',
                dataIndex: 'message',
                label: 'swag-paypal-pos.detail.logs.columnLastSync',
                sortable: true,
            }];
        },

        dateFilter() {
            return Shopware.Filter.getByName('date');
        },
    },

    methods: {
        getListCriteria() {
            const params = this.getMainListingParams();
            const criteria = new Criteria(this.page, this.limit);
            criteria.addFilter(Criteria.equals('runId', this.runId));
            criteria.addAssociation('posSalesChannelRun');

            criteria.addSorting(Criteria.sort(params.sortBy, params.sortDirection, params.naturalSorting));
            criteria.addSorting(Criteria.sort('level', 'DESC'));
            criteria.addSorting(Criteria.sort('createdAt', 'DESC'));

            return criteria;
        },

        getList() {
            this.loadingLogs = true;
            return this.logRepository.search(this.getListCriteria(), Context.api).then((result) => {
                this.logs = result;
                this.total = result.total;
                this.page = result.criteria.page;
                this.limit = result.criteria.limit;
                this.loadingLogs = false;
            });
        },

        getLabelVariantForItem(item) {
            if (item.posSalesChannelRun && item.posSalesChannelRun.status === 'cancelled') {
                return 'info';
            }

            return this.getLabelVariant(item.level);
        },

        getLabelForItem(item) {
            if (item.posSalesChannelRun && item.posSalesChannelRun.status === 'cancelled') {
                return 'swag-paypal-pos.detail.logs.states.aborted';
            }

            return this.getLabel(item.level);
        },
    },
});
