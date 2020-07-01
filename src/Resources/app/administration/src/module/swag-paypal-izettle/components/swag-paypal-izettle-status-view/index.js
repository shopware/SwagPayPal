import template from './swag-paypal-izettle-status-view.html.twig';

const { Component } = Shopware;

Component.register('swag-paypal-izettle-status-view', {
    template,

    props: {
        currentRun: {
            type: Object,
            required: false
        },
        lastFinishedRun: {
            type: Object,
            required: false
        },
        lastCompleteRun: {
            type: Object,
            required: false
        },
        isLoading: {
            type: Boolean,
            required: true
        },
        isSyncing: {
            type: Boolean,
            required: true
        },
        salesChannel: {
            type: Object,
            required: false
        },
        syncErrors: {
            type: Array,
            required: false
        }
    },

    data() {
        return {
            statusErrorLevel: null,
            statusCompleteErrorLevel: null,
            iconConfig: {
                syncing: 'default-arrow-360-full',
                warning: 'default-badge-warning',
                error: 'default-basic-x-line',
                success: 'default-basic-checkmark-line',
                noRunYet: 'default-action-more-horizontal'
            }
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
            return this.iconConfig[this.status] || 'default-badge-info';
        },

        statusIconComplete() {
            return this.iconConfig[this.statusCompleteErrorLevel];
        },

        noRunYet() {
            return this.salesChannel === null
                || this.salesChannel.id === null
                || (this.currentRun === null && this.lastFinishedRun === null);
        },

        incompleteLastRun() {
            return this.salesChannel !== null && this.salesChannel.id !== null
                && this.lastFinishedRun !== null && this.lastCompleteRun !== null
                && this.lastFinishedRun.id !== this.lastCompleteRun.id;
        },

        statusTitle() {
            let title = this.$tc(`swag-paypal-izettle.detail.base.status.message.${this.status}`);
            if (this.incompleteLastRun) {
                const task = this.$tc(`swag-paypal-izettle.detail.base.status.task.${this.lastFinishedRun.task}`);
                title += ` (${task})`;
            }
            return title;
        },

        disabled() {
            return this.salesChannel !== null && this.salesChannel.id !== null && this.salesChannel.active === false;
        }
    },

    watch: {
        lastFinishedRun() {
            this.statusErrorLevel = this.getHighestLevel(this.lastFinishedRun);
        },
        lastCompleteRun() {
            this.statusCompleteErrorLevel = this.getHighestLevel(this.lastCompleteRun);
        }
    },

    methods: {
        getHighestLevel(run) {
            const level = Math.max(...run.logs.map((log) => { return log.level; }));
            if (level >= 400) {
                return 'error';
            }
            if (level >= 300) {
                return 'warning';
            }
            return 'success';
        }
    }
});
