import template from './swag-paypal-izettle-detail-runs.html.twig';

const { Component, Context } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('swag-paypal-izettle-detail-runs', {
    template,

    inject: [
        'SwagPayPalIZettleApiService',
        'repositoryFactory'
    ],

    mixins: [
        'notification'
    ],

    props: {
        salesChannel: {
            type: Object,
            required: false
        }
    },

    data() {
        return {
            runs: [],
            runCriteria: null,
            runPage: 1,
            runLimit: 10,
            runTotal: 0,
            isLoading: false,
            isCleaningLog: false,
            showModal: false,
            currentRunId: '',
            columns: [
                { property: 'task', label: 'swag-paypal-izettle.detail.runs.columns.task', sortable: false },
                { property: 'state', label: 'swag-paypal-izettle.detail.runs.columns.state', sortable: false },
                { property: 'date', label: 'swag-paypal-izettle.detail.runs.columns.date', sortable: false }
            ]
        };
    },

    computed: {
        runRepository() {
            return this.repositoryFactory.create('swag_paypal_izettle_sales_channel_run');
        }
    },

    watch: {
        'salesChannel.id'() {
            this.createRunCriteria();
            this.fetchRuns();
        }
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.createRunCriteria();
            this.fetchRuns();
        },

        createRunCriteria() {
            if (this.salesChannel === null || this.salesChannel.id === null) {
                this.runCriteria = null;
                return;
            }

            this.runCriteria = new Criteria(this.runPage, this.runLimit);
            this.runCriteria.addFilter(Criteria.equals('salesChannelId', this.salesChannel.id));
            this.runCriteria.addFilter(Criteria.not('AND', [Criteria.equals('finishedAt', null)]));
            this.runCriteria.addSorting(Criteria.sort('finishedAt', 'DESC'));
            this.runCriteria.addAssociation('logs');
            this.runCriteria.getAssociation('logs').limit = 1;
            this.runCriteria.getAssociation('logs').addSorting(Criteria.sort('level', 'DESC'));
        },

        onPaginate({ page = 1, limit = 10 }) {
            this.runCriteria.setPage(page);
            this.runCriteria.setLimit(limit);

            return this.fetchRuns();
        },

        fetchRuns() {
            if (this.runCriteria === null) {
                return Promise.resolve();
            }

            this.isLoading = true;
            return this.runRepository.search(this.runCriteria, Context.api).then((result) => {
                this.runs = result;
                this.runTotal = result.total;
                this.runPage = result.criteria.page;
                this.runLimit = result.criteria.limit;
                this.isLoading = false;
            });
        },

        onShowModal(id) {
            this.currentRunId = id;
            this.showModal = true;
        },

        onCloseModal() {
            this.showModal = false;
            this.currentRunId = '';
        },

        onClearLogs() {
            this.isLoading = true;

            this.SwagPayPalIZettleApiService.startLogCleanup(this.salesChannel.id).then(() => {
                this.fetchRuns();
                this.isLoading = false;
            }).catch((errorResponse) => {
                if (errorResponse.response.data && errorResponse.response.data.errors) {
                    const message = errorResponse.response.data.errors.map((error) => {
                        return error.detail;
                    }).join(' / ');

                    this.createNotificationError({
                        title: this.$tc('global.default.error'),
                        message
                    });

                    this.fetchRuns();
                    this.isLoading = false;
                }
            });
        },

        getLabelVariant(item) {
            if (item.logs.length <= 0) {
                return 'success';
            }

            if (item.logs[0].level >= 400) {
                return 'danger';
            }

            if (item.logs[0].level >= 300) {
                return 'warning';
            }

            if (item.logs[0].level > 200) {
                return 'info';
            }

            return 'success';
        },

        getLabel(item) {
            if (item.logs.length <= 0) {
                return 'swag-paypal-izettle.detail.runs.states.successful';
            }

            if (item.logs[0].level > 200) {
                return 'swag-paypal-izettle.detail.runs.states.withWarnings';
            }

            return 'swag-paypal-izettle.detail.runs.states.successful';
        }
    }
});
