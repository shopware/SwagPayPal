import template from './swag-paypal-izettle-detail-overview.html.twig';

const { Component } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('swag-paypal-izettle-detail-overview', {
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
        isNewEntity: {
            type: Boolean,
            default: false
        }
    },

    data() {
        return {
            isSyncing: false,
            syncErrors: null,
            syncingRunId: null,
            lastFinishedRun: null,
            lastCompleteRun: null,
            statusErrorLevel: null,
            isLoading: false
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

    mounted() {
        this.mountedComponent();
    },

    methods: {
        createdComponent() {
            this.checkForSync();
            this.loadLastFinishedRun();
        },

        mountedComponent() {
            this.updateButtons();
        },

        forceUpdate() {
            this.$forceUpdate();
        },

        startSync(callable) {
            this.syncErrors = null;
            this.isSyncing = true;
            this.updateButtons();
            callable(this.salesChannel.id).then((response) => {
                this.syncingRunId = response.runId;
                this.updateSync();
            }).catch((errorResponse) => {
                this.syncErrors = errorResponse.response.data.errors;
                this.loadLastFinishedRun().then(() => {
                    this.isSyncing = false;
                    this.updateButtons();
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
                    this.updateButtons();
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

        onSyncAbort() {
            if (this.syncingRunId !== null) {
                this.SwagPayPalIZettleApiService.abortSync(this.syncingRunId).then(() => {
                    this.updateSync();
                });
            }
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
        },

        updateButtons() {
            const buttonConfig = [
                {
                    key: 'sync',
                    label: this.$tc('swag-paypal-izettle.detail.overview.buttonSync'),
                    variant: 'primary',
                    action: this.onStartSync,
                    disabled: !(this.salesChannel && this.salesChannel.active),
                    isLoading: this.isSyncing
                }
            ];

            this.$emit('buttons-update', buttonConfig);
        }
    }
});
