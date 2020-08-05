import template from './swag-paypal-izettle-detail-base.html.twig';

const { Component } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('swag-paypal-izettle-detail-base', {
    template,

    inject: [
        'SwagPayPalIZettleApiService',
        'salesChannelService',
        'repositoryFactory'
    ],

    mixins: [
        'placeholder'
    ],

    props: {
        salesChannel: {
            type: Object,
            required: false
        },
        isLoading: {
            type: Boolean,
            default: false
        },
        isNewEntity: {
            type: Boolean,
            default: false
        }
    },

    data() {
        return {
            isSyncing: false,
            syncErrors: null,
            showDeleteModal: false,
            syncingRunId: null,
            lastFinishedRun: null,
            lastCompleteRun: null,
            statusErrorLevel: null
        };
    },

    computed: {
        salesChannelRepository() {
            return this.repositoryFactory.create('sales_channel');
        },

        runRepository() {
            return this.repositoryFactory.create('swag_paypal_izettle_sales_channel_run');
        }
    },

    watch: {
        'salesChannel.id'() {
            this.checkForSync();
            this.loadLastFinishedRun();
        }
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.checkForSync();
            this.loadLastFinishedRun();
        },

        onCloseDeleteModal() {
            this.showDeleteModal = false;
        },

        onConfirmDelete() {
            this.showDeleteModal = false;

            this.$nextTick(() => {
                this.deleteSalesChannel(this.salesChannel.id);
                this.$router.push({ name: 'sw.dashboard.index' });
            });
        },

        deleteSalesChannel(salesChannelId) {
            this.salesChannelRepository.delete(salesChannelId, Shopware.Context.api).then(() => {
                this.$root.$emit('sales-channel-change');
            });
        },

        forceUpdate() {
            this.$forceUpdate();
        },

        startSync(callable) {
            this.syncErrors = null;
            this.isSyncing = true;
            callable(this.salesChannel.id).then((response) => {
                this.syncingRunId = response.runId;
                this.updateSync();
            }).catch((errorResponse) => {
                this.syncErrors = errorResponse.response.data.errors;
                this.loadLastFinishedRun().then(() => {
                    this.isSyncing = false;
                });
            });
        },

        updateSync() {
            if (this.runId === null) {
                return;
            }

            this.runRepository.get(this.syncingRunId, Shopware.Context.api).then((entity) => {
                if (entity.finishedAt === null) {
                    setTimeout(this.updateSync, 1500);
                    return;
                }

                this.syncingRunId = null;
                this.loadLastFinishedRun().then(() => {
                    this.isSyncing = false;
                });
            });
        },

        onStartSync() {
            this.startSync(this.SwagPayPalIZettleApiService.startCompleteSync.bind(this.SwagPayPalIZettleApiService));
        },

        onStartProductSync() {
            this.startSync(this.SwagPayPalIZettleApiService.startProductSync.bind(this.SwagPayPalIZettleApiService));
        },

        onStartImageSync() {
            this.startSync(this.SwagPayPalIZettleApiService.startImageSync.bind(this.SwagPayPalIZettleApiService));
        },

        onStartInventorySync() {
            this.startSync(this.SwagPayPalIZettleApiService.startInventorySync.bind(this.SwagPayPalIZettleApiService));
        },

        loadLastFinishedRun(needComplete = false) {
            if (this.salesChannel === null || this.salesChannel.id === null) {
                this.lastFinishedRun = null;
                return Promise.resolve();
            }

            const criteria = new Criteria(1, 1);
            criteria.addFilter(Criteria.equals('salesChannelId', this.salesChannel.id));
            criteria.addFilter(Criteria.not('AND', [Criteria.equals('finishedAt', null)]));
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

                this.lastFinishedRun = result.first();
                if (this.lastFinishedRun !== null && this.lastFinishedRun.task !== 'complete') {
                    this.loadLastFinishedRun(true);
                } else {
                    this.lastCompleteRun = this.lastFinishedRun;
                }
                this.$forceUpdate();
            });
        },

        checkForSync() {
            if (this.salesChannel === null || this.salesChannel.id === null) {
                return;
            }

            const criteria = new Criteria(1, 1);
            criteria.addFilter(Criteria.equals('salesChannelId', this.salesChannel.id));
            criteria.addFilter(Criteria.equals('finishedAt', null));
            criteria.addSorting(Criteria.sort('createdAt', 'DESC'));

            this.runRepository.search(criteria, Shopware.Context.api).then((result) => {
                if (result.first() === null) {
                    return;
                }
                this.isSyncing = true;
                this.syncingRunId = result.first().id;
                this.updateSync();
            });
        }
    }
});
