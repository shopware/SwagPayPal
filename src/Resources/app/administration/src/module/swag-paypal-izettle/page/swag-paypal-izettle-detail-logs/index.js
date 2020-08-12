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
        'placeholder',
        'notification'
    ],

    props: {
        salesChannel: {
            type: Object,
            required: false
        },
        isNewEntity: {
            type: Boolean,
            default: false
        }
    },

    data() {
        return {
            products: [],
            productPage: 1,
            productLimit: 10,
            productTotal: 10,
            loadingProducts: false,
            logs: [],
            logCriteria: null,
            logPage: 1,
            logLimit: 10,
            logTotal: 10,
            loadingLogs: false,
            isLoading: false,
            isCleaningLog: false,
            isCleanLogSuccessful: false
        };
    },

    computed: {
        logRepository() {
            return this.repositoryFactory.create('swag_paypal_izettle_sales_channel_run_log');
        }
    },

    watch: {
        'salesChannel.id'() {
            this.doProductSearch();
            this.createLogCriteria();
            this.doLogSearch();
        }
    },

    created() {
        this.createdComponent();
    },

    mounted() {
        this.mountedComponent();
    },

    methods: {
        createdComponent() {
            this.doProductSearch();
            this.createLogCriteria();
            this.doLogSearch();
        },

        mountedComponent() {
            this.updateButtons();
        },

        createLogCriteria() {
            if (this.salesChannel === null || this.salesChannel.id === null) {
                this.logCriteria = null;
                return;
            }

            this.logCriteria = new Criteria(this.logPage, this.logLimit);
            this.logCriteria.addFilter(Criteria.equals('productId', null));
            this.logCriteria.addAssociation('run');
            this.logCriteria.addFilter(Criteria.equals('run.salesChannelId', this.salesChannel.id));
            this.logCriteria.addSorting(Criteria.sort('run.createdAt', 'DESC'));
            this.logCriteria.addSorting(Criteria.sort('level', 'DESC'));
            this.logCriteria.addSorting(Criteria.sort('createdAt', 'DESC'));
        },

        paginateProducts({ page = 1, limit = 10 }) {
            this.productPage = page;
            this.productLimit = limit;

            return this.doProductSearch();
        },

        paginateLogs({ page = 1, limit = 10 }) {
            this.logCriteria.setPage(page);
            this.logCriteria.setLimit(limit);

            return this.doLogSearch();
        },

        doProductSearch() {
            if (this.salesChannel === null || this.salesChannel.id === null) {
                this.logCriteria = null;
                return Promise.resolve();
            }

            this.loadingProducts = true;
            return this.SwagPayPalIZettleApiService.getProductLog(
                this.salesChannel.id,
                this.productPage,
                this.productLimit
            ).then((result) => {
                this.products = Object.values(result.elements);
                this.productTotal = result.total;
                this.loadingProducts = false;
            });
        },

        doLogSearch() {
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

        iconClass(item) {
            if (item.level >= 400) {
                return 'swag-paypal-izettle-detail-log-entry--error';
            }
            if (item.level >= 300) {
                return 'swag-paypal-izettle-detail-log-entry--warning';
            }
            if (item.level > 200) {
                return 'swag-paypal-izettle-detail-log-entry--info';
            }
            return 'swag-paypal-izettle-detail-log-entry--success';
        },

        icon(item) {
            if (item.level >= 400) {
                return 'default-badge-error';
            }
            if (item.level >= 300) {
                return 'default-badge-warning';
            }
            if (item.level > 200) {
                return 'default-badge-info';
            }
            return 'default-basic-checkmark-circle';
        },

        onCleanLog() {
            this.isCleaningLog = true;
            this.isCleanLogSuccessful = false;
            this.updateButtons();

            this.SwagPayPalIZettleApiService.startLogCleanup(this.salesChannel.id).then(() => {
                this.isCleaningLog = false;
                this.isCleanLogSuccessful = true;
                this.updateButtons();
            }).catch((errorResponse) => {
                if (errorResponse.response.data && errorResponse.response.data.errors) {
                    let message = '';
                    message += errorResponse.response.data.errors.map((error) => {
                        return error.detail;
                    }).join(' / ');

                    this.createNotificationError({
                        title: this.$tc('global.default.error'),
                        message
                    });

                    this.isCleaningLog = false;
                    this.isCleanLogSuccessful = false;
                    this.updateButtons();
                }
            });
        },

        updateButtons() {
            const buttonConfig = [
                {
                    key: 'clean',
                    label: this.$tc('swag-paypal-izettle.detail.cleanLog'),
                    variant: '',
                    action: this.onCleanLog,
                    disabled: false,
                    isLoading: this.isCleaningLog
                }
            ];

            this.$emit('buttons-update', buttonConfig);
        }
    }
});
