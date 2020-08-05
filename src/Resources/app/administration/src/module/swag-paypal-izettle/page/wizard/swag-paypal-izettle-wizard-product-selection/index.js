import template from './swag-paypal-izettle-wizard-product-selection.html.twig';

const { Component } = Shopware;

Component.register('swag-paypal-izettle-wizard-product-selection', {
    template,

    props: {
        salesChannel: {
            type: Object,
            required: true
        },
        cloneSalesChannelId: {
            type: String,
            required: false
        },
        saveSalesChannel: {
            type: Function,
            required: true
        }
    },

    data() {
        return {
            manualSalesChannel: false,
            hasClone: false
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
            }
        }
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
            this.$emit('frw-set-title', this.$tc('swag-paypal-izettle.wizard.product-selection.modalTitle'));
        },

        updateButtons() {
            const buttonConfig = [
                {
                    key: 'back',
                    label: this.$tc('sw-first-run-wizard.general.buttonBack'),
                    position: 'left',
                    action: this.routeBackToCustomization,
                    disabled: false
                },
                {
                    key: 'next',
                    label: this.$tc('sw-first-run-wizard.general.buttonNext'),
                    position: 'right',
                    variant: 'primary',
                    action: this.routeToSync,
                    disabled: false
                }
            ];

            this.$emit('buttons-update', buttonConfig);
        },

        routeBackToCustomization() {
            this.$router.push({
                name: 'swag.paypal.izettle.wizard.customization',
                params: { id: this.salesChannel.id }
            });
        },

        routeToSync() {
            this.toggleLoadingState(true);

            this.saveSalesChannel().then(() => {
                this.toggleLoadingState(false);
                this.$router.push({
                    name: 'swag.paypal.izettle.wizard.sync',
                    params: { id: this.salesChannel.id }
                });
            }).finally(() => {
                this.toggleLoadingState(false);
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
        }
    }
});
