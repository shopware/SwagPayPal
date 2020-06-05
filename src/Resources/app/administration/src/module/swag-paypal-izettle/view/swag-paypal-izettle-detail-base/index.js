import template from './swag-paypal-izettle-detail-base.html.twig';

const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('swag-paypal-izettle-detail-base', {
    template,

    inject: [
        'SwagPayPalIZettleApiService',
        'salesChannelService',
        'repositoryFactory'
    ],

    mixins: [
        Mixin.getByName('placeholder')
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
            currentRun: null,
            lastFinishedRun: null,
            statusErrorLevel: null
        };
    },

    computed: {
        salesChannelRepository() {
            return this.repositoryFactory.create('sales_channel');
        },

        runRepository() {
            return this.repositoryFactory.create('swag_paypal_izettle_sales_channel_run');
        },

        status() {
            if (this.isSyncing) {
                return 'syncing';
            }
            if (this.noRunYet) {
                return 'noRunYet';
            }
            return this.statusErrorLevel;
        },

        statusVariant() {
            if (this.isSyncing || this.noRunYet) {
                return 'info';
            }
            return this.statusErrorLevel;
        },

        statusIcon() {
            const iconConfig = {
                syncing: 'default-arrow-360-full',
                warning: 'default-badge-warning',
                error: 'default-basic-x-line',
                success: 'default-basic-checkmark-line',
                noRunYet: 'default-action-more-horizontal'
            };

            return iconConfig[this.status] || 'default-badge-info';
        },

        noRunYet() {
            return this.salesChannel === null || this.salesChannel.id === null || this.currentRun === null;
        }
    },

    watch: {
        'salesChannel.id'() {
            this.loadCurrentRun();
            this.loadLastFinishedRun();
        }
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.loadCurrentRun();
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

        onStartSync() {
            this.syncErrors = null;
            this.isSyncing = true;
            this.SwagPayPalIZettleApiService.startSync(this.salesChannel.id).catch((errorResponse) => {
                this.syncErrors = errorResponse.response.data.errors;
            }).finally(() => {
                this.loadLastFinishedRun().then(() => {
                    this.isSyncing = false;
                });
            });
        },

        onStartProductSync() {
            this.syncErrors = null;
            this.isSyncing = true;
            this.SwagPayPalIZettleApiService.startProductSync(this.salesChannel.id).catch((errorResponse) => {
                this.syncErrors = errorResponse.response.data.errors;
            }).finally(() => {
                this.loadLastFinishedRun().then(() => {
                    this.isSyncing = false;
                });
            });
        },

        onStartInventorySync() {
            this.syncErrors = null;
            this.isSyncing = true;
            this.SwagPayPalIZettleApiService.startInventorySync(this.salesChannel.id).catch((errorResponse) => {
                this.syncErrors = errorResponse.response.data.errors;
            }).finally(() => {
                this.loadLastFinishedRun().then(() => {
                    this.isSyncing = false;
                });
            });
        },

        loadCurrentRun() {
            if (this.salesChannel === null || this.salesChannel.id === null) {
                this.currentRun = null;
                return Promise.resolve();
            }

            const criteria = new Criteria(1, 1);
            criteria.addFilter(Criteria.equals('salesChannelId', this.salesChannel.id));
            criteria.addSorting(Criteria.sort('createdAt', 'DESC'));

            return this.runRepository.search(criteria, Shopware.Context.api).then((result) => {
                this.currentRun = result.first();
                if (this.currentRun !== null) {
                    this.isSyncing = this.currentRun.updatedAt === null;
                    if (this.isSyncing) {
                        setTimeout(this.loadCurrentRun, 3000);
                    }
                }
                this.$forceUpdate();
            });
        },

        loadLastFinishedRun() {
            if (this.salesChannel === null || this.salesChannel.id === null) {
                this.lastFinishedRun = null;
                return Promise.resolve();
            }

            const criteria = new Criteria(1, 1);
            criteria.addFilter(Criteria.equals('salesChannelId', this.salesChannel.id));
            criteria.addFilter(Criteria.not('AND', [Criteria.equals('updatedAt', null)]));
            criteria.addSorting(Criteria.sort('createdAt', 'DESC'));
            criteria.addAssociation('logs');

            return this.runRepository.search(criteria, Shopware.Context.api).then((result) => {
                this.lastFinishedRun = result.first();
                this.statusErrorLevel = this.getHighestLevel(this.lastFinishedRun);
                this.$forceUpdate();
            });
        },

        getHighestLevel(run) {
            const level = Math.max(...run.logs.map((log) => { return log.level; }));
            if (level > 400) {
                return 'error';
            }
            if (level > 300) {
                return 'warning';
            }
            return 'success';
        }
    }
});
