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
        'swag-paypal-izettle-log-label'
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
            logCriteria: null,
            logPage: 1,
            logLimit: 10,
            logTotal: 0,
            loadingLogs: false,
            isLoading: false,
            columns: [
                { property: 'date', label: 'swag-paypal-izettle.detail.syncedProducts.columns.date', sortable: false },
                { property: 'state', label: 'swag-paypal-izettle.detail.syncedProducts.columns.state', sortable: false },
                { property: 'log', label: 'swag-paypal-izettle.detail.logs.columnLastSync', sortable: false }
            ]
        };
    },

    computed: {
        logRepository() {
            return this.repositoryFactory.create('swag_paypal_izettle_sales_channel_run_log');
        }
    },

    watch: {
        'salesChannel.id'() {
            this.createLogCriteria();
            this.fetchLogs();
        }
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.createLogCriteria();
            this.fetchLogs();
        },

        createLogCriteria() {
            this.logCriteria = new Criteria(this.logPage, this.logLimit);
            this.logCriteria.addFilter(Criteria.equals('runId', this.runId));
            this.logCriteria.addAssociation('run');
            this.logCriteria.addSorting(Criteria.sort('level', 'DESC'));
            this.logCriteria.addSorting(Criteria.sort('createdAt', 'DESC'));
        },

        onPaginateLogs({ page = 1, limit = 10 }) {
            this.logCriteria.setPage(page);
            this.logCriteria.setLimit(limit);

            return this.fetchLogs();
        },

        fetchLogs() {
            if (this.logCriteria === null) {
                return Promise.resolve();
            }

            this.loadingLogs = true;
            return this.logRepository.search(this.logCriteria, Context.api).then((result) => {
                this.logs = result;
                this.logTotal = result.total;
                this.logPage = result.criteria.page;
                this.logLimit = result.criteria.limit;
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
