import template from './swag-paypal-pos-detail-runs.html.twig';
import './swag-paypal-pos-detail-runs.scss';

const { Component, Context } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('swag-paypal-pos-detail-runs', {
    template,

    inject: [
        'SwagPayPalPosApiService',
        'repositoryFactory',
        'acl',
    ],

    mixins: [
        Shopware.Mixin.getByName('swag-paypal-pos-catch-error'),
        Shopware.Mixin.getByName('notification'),
        Shopware.Mixin.getByName('listing'),
    ],

    props: {
        salesChannel: {
            type: Object,
            required: false,
            default: null,
        },
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
        };
    },

    computed: {
        runRepository() {
            return this.repositoryFactory.create('swag_paypal_pos_sales_channel_run');
        },

        columns() {
            return [{
                property: 'task',
                dataIndex: 'task',
                label: 'swag-paypal-pos.detail.runs.columns.task',
                sortable: true,
            }, {
                property: 'state',
                dataIndex: 'logs.level',
                label: 'swag-paypal-pos.detail.runs.columns.state',
                sortable: true,
            }, {
                property: 'date',
                dataIndex: 'finishedAt',
                label: 'swag-paypal-pos.detail.runs.columns.date',
                sortable: true,
            }];
        },

        dateFilter() {
            return Shopware.Filter.getByName('date');
        },
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
            criteria.addFilter(Criteria.not('AND', [Criteria.equals('status', 'in_progress')]));

            criteria.addAssociation('logs');
            criteria.getAssociation('logs').limit = 1;
            criteria.getAssociation('logs').addSorting(Criteria.sort('level', 'DESC'));

            const params = this.getMainListingParams();
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

            return this.SwagPayPalPosApiService.startLogCleanup(this.salesChannel.id).then(() => {
                this.$emit('run-update');
                return this.getList();
            }).catch((errorResponse) => {
                this.catchError(null, errorResponse);
                this.getList();
            });
        },

        getLabelVariant(item) {
            if (item.status === 'cancelled') {
                return 'info';
            }

            if (item.status === 'failed') {
                return 'danger';
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
            if (item.status === 'cancelled') {
                return 'swag-paypal-pos.detail.runs.states.aborted';
            }

            if (item.status === 'failed') {
                return 'swag-paypal-pos.detail.runs.states.failed';
            }

            if (item.logs.length <= 0) {
                return 'swag-paypal-pos.detail.runs.states.successful';
            }

            if (item.logs[0].level > 200) {
                return 'swag-paypal-pos.detail.runs.states.withWarnings';
            }

            return 'swag-paypal-pos.detail.runs.states.successful';
        },
    },
});
