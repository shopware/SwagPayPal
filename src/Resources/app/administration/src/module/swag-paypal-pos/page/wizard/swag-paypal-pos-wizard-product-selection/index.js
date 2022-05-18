import template from './swag-paypal-pos-wizard-product-selection.html.twig';

const { Component } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('swag-paypal-pos-wizard-product-selection', {
    template,

    props: {
        salesChannel: {
            type: Object,
            required: true,
        },
        cloneSalesChannelId: {
            type: String,
            required: false,
            default: null,
        },
        saveSalesChannel: {
            type: Function,
            required: true,
        },
    },

    data() {
        return {
            manualSalesChannel: false,
            hasClone: false,
        };
    },

    computed: {
        localCloneSalesChannelId: {
            get() {
                this.updateButtons();
                return this.cloneSalesChannelId;
            },
            set(cloneSalesChannelId) {
                this.$emit('update-clone-sales-channel', cloneSalesChannelId);
            },
        },

        salesChannelCriteria() {
            const criteria = new Criteria(1, 500);
            criteria.addFilter(Criteria.not('and', [
                Criteria.equals('id', this.salesChannel.id),
            ]));

            return criteria;
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.updateButtons();
            this.setTitle();
        },

        setTitle() {
            this.$emit('frw-set-title', this.$tc('swag-paypal-pos.wizard.productSelection.modalTitle'));
        },

        updateButtons() {
            const buttonConfig = [
                {
                    key: 'back',
                    label: this.$tc('sw-first-run-wizard.general.buttonBack'),
                    position: 'left',
                    action: this.routeBackToCustomization,
                    disabled: false,
                },
                {
                    key: 'next',
                    label: this.$tc('sw-first-run-wizard.general.buttonNext'),
                    position: 'right',
                    variant: 'primary',
                    action: this.routeToSyncLibrary,
                    disabled: false,
                },
            ];

            this.$emit('buttons-update', buttonConfig);
        },

        routeBackToCustomization() {
            this.$router.push({
                name: 'swag.paypal.pos.wizard.customization',
                params: { id: this.salesChannel.id },
            });
        },

        routeToSyncLibrary() {
            this.$router.push({
                name: 'swag.paypal.pos.wizard.syncLibrary',
                params: { id: this.salesChannel.id },
            });
        },

        updateClone() {
            this.$emit('update-clone-sales-channel', null);
            this.forceUpdate();
        },

        forceUpdate() {
            this.$forceUpdate();
            this.updateButtons();
        },

        toggleLoadingState(state) {
            this.isConnecting = state;
            this.$emit('toggle-loading', state);
        },
    },
});
