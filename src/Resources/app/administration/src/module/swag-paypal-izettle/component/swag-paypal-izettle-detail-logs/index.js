import template from './swag-paypal-izettle-detail-logs.html.twig';
import './swag-paypal-izettle-detail-logs.scss';

const { Component, Context } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('swag-paypal-izettle-detail-logs', {
    template,

    inject: [
        'SwagPayPalIZettleApiService',
        'repositoryFactory'
    ],

    mixins: [
        'notification',
        'swag-paypal-izettle-log-label',
        'listing'
    ],

    props: {
        runId: {
            type: String,
            required: true
        }
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
            columns: [
                {
                    property: 'date',
                    dataIndex: 'createdAt',
                    label: 'swag-paypal-izettle.detail.syncedProducts.columns.date',
                    width: '140px',
                    sortable: true
                },
                {
                    property: 'state',
                    dataIndex: 'level',
                    label: 'swag-paypal-izettle.detail.syncedProducts.columns.state',
                    width: '120px',
                    sortable: true
                },
                {
                    property: 'message',
                    dataIndex: 'message',
                    label: 'swag-paypal-izettle.detail.logs.columnLastSync',
                    sortable: true
                }
            ]
        };
    },

    computed: {
        logRepository() {
            return this.repositoryFactory.create('swag_paypal_izettle_sales_channel_run_log');
        }
    },

    methods: {
        getListCriteria() {
            const params = this.getListingParams();
            const criteria = new Criteria(this.page, this.limit);
            criteria.addFilter(Criteria.equals('runId', this.runId));
            criteria.addAssociation('run');

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
            if (item.run && item.run.abortedByUser) {
                return 'info';
            }

            return this.getLabelVariant(item.level);
        },

        getLabelForItem(item) {
            if (item.run && item.run.abortedByUser) {
                return 'swag-paypal-izettle.detail.logs.states.aborted';
            }

            return this.getLabel(item.level);
        }
    }
});
