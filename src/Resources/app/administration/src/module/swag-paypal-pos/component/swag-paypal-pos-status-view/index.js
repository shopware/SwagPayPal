import template from './swag-paypal-pos-status-view.html.twig';

const { Component } = Shopware;

Component.register('swag-paypal-pos-status-view', {
    template,

    props: {
        lastFinishedRun: {
            type: Object,
            required: false,
            default: null,
        },
        lastCompleteRun: {
            type: Object,
            required: false,
            default: null,
        },
        isLoading: {
            type: Boolean,
            default: false,
        },
        isSyncing: {
            type: Boolean,
            default: false,
        },
        salesChannel: {
            type: Object,
            required: false,
            default: null,
        },
        syncErrors: {
            type: Array,
            required: false,
            default: null,
        },
    },

    data() {
        return {
            statusErrorLevel: null,
            statusCompleteErrorLevel: null,
            iconConfig: {
                syncing: 'regular-sync',
                warning: 'regular-exclamation-triangle',
                error: 'regular-times-xs',
                success: 'regular-checkmark',
                noRunYet: 'regular-blocked-circle',
            },
        };
    },

    computed: {
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
            return this.iconConfig[this.status] || 'regular-info-circle';
        },

        statusIconComplete() {
            return this.iconConfig[this.statusCompleteErrorLevel];
        },

        noRunYet() {
            return this.salesChannel === null
                || this.salesChannel.id === null
                || this.lastFinishedRun === null;
        },

        incompleteLastRun() {
            return this.salesChannel !== null && this.salesChannel.id !== null
                && this.lastFinishedRun !== null && this.lastCompleteRun !== null
                && this.lastFinishedRun.id !== this.lastCompleteRun.id;
        },

        statusTitle() {
            let title = this.$tc(`swag-paypal-pos.detail.overview.status.message.${this.status}`);
            if (this.incompleteLastRun) {
                const task = this.$tc(`swag-paypal-pos.detail.overview.status.task.${this.lastFinishedRun.task}`);
                if (!this.isSyncing) {
                    title += ` (${task})`;
                }
            }

            if (this.lastFinishedRun && this.lastFinishedRun.status === 'cancelled' && !this.isSyncing) {
                title = this.$tc('swag-paypal-pos.detail.overview.status.message.aborted');
            }

            return title;
        },

        disabled() {
            return this.salesChannel !== null && this.salesChannel.id !== null && this.salesChannel.active === false;
        },

        dateFilter() {
            return Shopware.Filter.getByName('date');
        },
    },

    watch: {
        lastFinishedRun: {
            handler() {
                this.statusErrorLevel = this.getHighestLevel(this.lastFinishedRun);
            },
            immediate: true,
        },
        lastCompleteRun: {
            handler() {
                this.statusCompleteErrorLevel = this.getHighestLevel(this.lastCompleteRun);
            },
            immediate: true,
        },
    },

    methods: {
        getHighestLevel(run) {
            if (run === null) {
                return null;
            }

            if (run.status === 'cancelled') {
                return 'info';
            }

            const level = Math.max(...run.logs.map((log) => { return log.level; }));
            if (level >= 400) {
                return 'error';
            }

            if (level >= 300) {
                return 'warning';
            }

            return 'success';
        },
    },
});
