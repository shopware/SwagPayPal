import template from './swag-paypal-izettle-detail-runs.html.twig';
import './swag-paypal-izettle-detail-runs.scss';

const { Component, Context } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('swag-paypal-izettle-detail-runs', {
    template,

    inject: [
        'SwagPayPalIZettleApiService',
        'repositoryFactory'
    ],

    mixins: [
        'notification',
        'listing'
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
            limit: 10,
            sortBy: 'finishedAt',
            sortDirection: 'DESC',
            isLoading: false,
            isCleaningLog: false,
            showModal: false,
            currentRunId: '',
            columns: [
                {
                    property: 'task',
                    dataIndex: 'task',
                    label: 'swag-paypal-izettle.detail.runs.columns.task',
                    sortable: true
                },
                {
                    property: 'state',
                    dataIndex: 'logs.level',
                    label: 'swag-paypal-izettle.detail.runs.columns.state',
                    sortable: true
                },
                {
                    property: 'date',
                    dataIndex: 'finishedAt',
                    label: 'swag-paypal-izettle.detail.runs.columns.date',
                    sortable: true
                }
            ]
        };
    },

    computed: {
        runRepository() {
            return this.repositoryFactory.create('swag_paypal_izettle_sales_channel_run');
        }
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.$emit('buttons-update', []);
        },

        getListCriteria() {
            const criteria = new Criteria(this.page, this.limit);
            criteria.addFilter(Criteria.equals('salesChannelId', this.salesChannel.id));
            criteria.addFilter(Criteria.not('AND', [Criteria.equals('finishedAt', null)]));

            criteria.addAssociation('logs');
            criteria.getAssociation('logs').limit = 1;
            criteria.getAssociation('logs').addSorting(Criteria.sort('level', 'DESC'));

            const params = this.getListingParams();
            criteria.addSorting(Criteria.sort(params.sortBy, params.sortDirection, params.naturalSorting));
            criteria.addSorting(Criteria.sort('finishedAt', 'DESC'));

            return criteria;
        },

        getList() {
            if (this.salesChannel === null || this.salesChannel.id === null) {
                return Promise.resolve();
            }

            this.isLoading = true;
            return this.runRepository.search(this.getListCriteria(), Context.api).then((result) => {
                this.runs = result;
                this.total = result.total;
                this.page = result.criteria.page;
                this.limit = result.criteria.limit;
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

            return this.SwagPayPalIZettleApiService.startLogCleanup(this.salesChannel.id).then(() => {
                return this.getList();
            }).catch((errorResponse) => {
                if (errorResponse.response.data && errorResponse.response.data.errors) {
                    const message = errorResponse.response.data.errors.map((error) => {
                        return error.detail;
                    }).join(' / ');

                    this.createNotificationError({
                        title: this.$tc('global.default.error'),
                        message
                    });

                    this.getList();
                }
            });
        },

        getLabelVariant(item) {
            if (item.abortedByUser) {
                return 'info';
            }

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
            if (item.abortedByUser) {
                return 'swag-paypal-izettle.detail.runs.states.aborted';
            }

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
