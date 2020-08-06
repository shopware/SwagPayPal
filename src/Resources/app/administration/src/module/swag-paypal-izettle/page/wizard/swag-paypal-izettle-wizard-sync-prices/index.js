import template from './swag-paypal-izettle-wizard-sync-prices.html.twig';
import './swag-paypal-izettle-wizard-sync-prices.scss';

const { Component } = Shopware;

Component.register('swag-paypal-izettle-wizard-sync-prices', {
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

    computed: {
        optionTrue() {
            return {
                name: this.$tc('swag-paypal-izettle.wizard.sync-prices.optionTrueLabel'),
                description: this.$tc('swag-paypal-izettle.wizard.sync-prices.optionTrueDescription')
            };
        },

        optionFalse() {
            return {
                name: this.$tc('swag-paypal-izettle.wizard.sync-prices.optionFalseLabel'),
                description: this.$tc('swag-paypal-izettle.wizard.sync-prices.optionFalseDescription')
            };
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
            this.$emit('frw-set-title', this.$tc('swag-paypal-izettle.wizard.sync-prices.modalTitle'));
        },

        updateButtons() {
            const buttonConfig = [
                {
                    key: 'back',
                    label: this.$tc('sw-first-run-wizard.general.buttonBack'),
                    position: 'left',
                    action: this.routeBackToProductSelection,
                    disabled: false
                },
                {
                    key: 'next',
                    label: this.$tc('sw-first-run-wizard.general.buttonNext'),
                    position: 'right',
                    variant: 'primary',
                    action: this.routeToSyncLibrary,
                    disabled: false
                }
            ];

            this.$emit('buttons-update', buttonConfig);
        },

        routeBackToProductSelection() {
            this.$router.push({
                name: 'swag.paypal.izettle.wizard.product-selection',
                params: { id: this.salesChannel.id }
            });
        },

        routeToSyncLibrary() {
            this.toggleLoadingState(true);

            this.saveSalesChannel().then(() => {
                this.toggleLoadingState(false);
                this.$router.push({
                    name: 'swag.paypal.izettle.wizard.sync-library',
                    params: { id: this.salesChannel.id }
                });
            }).finally(() => {
                this.toggleLoadingState(false);
            });
        },

        forceUpdate() {
            this.$forceUpdate();
        },

        toggleLoadingState(state) {
            this.isConnecting = state;
            this.$emit('toggle-loading', state);
        }
    }
});
