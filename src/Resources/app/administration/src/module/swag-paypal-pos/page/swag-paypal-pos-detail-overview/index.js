import template from './swag-paypal-pos-detail-overview.html.twig';
import './swag-paypal-pos-detail-overview.scss';

const { Component } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('swag-paypal-pos-detail-overview', {
    template,

    inject: [
        'SwagPayPalPosApiService',
        'salesChannelService',
        'repositoryFactory',
    ],

    mixins: [
        Shopware.Mixin.getByName('placeholder'),
    ],

    props: {
        salesChannel: {
            type: Object,
            required: false,
            default: null,
        },
        lastRun: {
            type: Object,
            required: false,
            default: null,
        },
        lastCompleteRun: {
            type: Object,
            required: false,
            default: null,
        },
    },

    data() {
        return {
            isSyncing: false,
            syncErrors: null,
            syncingRunId: null,
            statusErrorLevel: null,
            isLoading: false,
        };
    },

    computed: {
        runRepository() {
            return this.repositoryFactory.create('swag_paypal_pos_sales_channel_run');
        },
    },

    watch: {
        'salesChannel.id'() {
            this.checkForSync();
        },
        lastRun() {
            this.$forceUpdate();
        },
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
            this.updateButtons(true);
            callable(this.salesChannel.id).then((response) => {
                this.syncingRunId = response.runId;
                this.updateSync();
            }).catch((errorResponse) => {
                this.syncErrors = errorResponse.response.data.errors;
                this.$emit('run-update');
                this.isSyncing = false;
                this.updateButtons();
            });
        },

        updateSync() {
            if (this.syncingRunId === null) {
                return;
            }

            this.runRepository.get(this.syncingRunId, Shopware.Context.api).then((entity) => {
                if (entity !== null && entity.status === 'in_progress') {
                    setTimeout(this.updateSync, 1500);
                    return;
                }

                this.syncingRunId = null;
                this.$emit('run-update');
                this.isSyncing = false;
                this.updateButtons();
            });
        },

        onStartSync() {
            this.startSync(this.SwagPayPalPosApiService.startCompleteSync.bind(this.SwagPayPalPosApiService));
        },

        onStartProductSync() {
            this.startSync(this.SwagPayPalPosApiService.startProductSync.bind(this.SwagPayPalPosApiService));
        },

        onStartImageSync() {
            this.startSync(this.SwagPayPalPosApiService.startImageSync.bind(this.SwagPayPalPosApiService));
        },

        onStartInventorySync() {
            this.startSync(this.SwagPayPalPosApiService.startInventorySync.bind(this.SwagPayPalPosApiService));
        },

        onSyncAbort() {
            if (this.syncingRunId !== null) {
                this.SwagPayPalPosApiService.abortSync(this.syncingRunId).then(() => {
                    this.updateSync();
                });
            }
        },

        checkForSync() {
            if (this.salesChannel === null || this.salesChannel.id === null) {
                return;
            }

            const criteria = new Criteria(1, 1);
            criteria.addFilter(Criteria.equals('salesChannelId', this.salesChannel.id));
            criteria.addFilter(Criteria.equals('status', 'in_progress'));
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

        updateButtons(syncing = false) {
            const buttonConfig = [
                {
                    key: 'sync',
                    label: this.$tc('swag-paypal-pos.detail.overview.buttonSync'),
                    variant: 'primary',
                    action: this.onStartSync,
                    disabled: !(this.salesChannel && this.salesChannel.active),
                    isLoading: this.isSyncing,
                },
            ];

            if (syncing) {
                buttonConfig.unshift(
                    {
                        key: 'abortSync',
                        label: this.$tc('swag-paypal-pos.detail.overview.buttonSyncAbort'),
                        action: this.onSyncAbort,
                        disabled: !(this.salesChannel && this.salesChannel.active),
                    },
                );
            }

            this.$emit('buttons-update', buttonConfig);
        },
    },
});
