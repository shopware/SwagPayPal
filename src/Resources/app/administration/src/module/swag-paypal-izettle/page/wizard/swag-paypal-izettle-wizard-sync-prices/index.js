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
                name: this.$tc('swag-paypal-izettle.wizard.syncPrices.optionTrueLabel'),
                description: this.$tc('swag-paypal-izettle.wizard.syncPrices.optionTrueDescription')
            };
        },

        optionFalse() {
            return {
                name: this.$tc('swag-paypal-izettle.wizard.syncPrices.optionFalseLabel'),
                description: this.$tc('swag-paypal-izettle.wizard.syncPrices.optionFalseDescription')
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
            this.$emit('frw-set-title', this.$tc('swag-paypal-izettle.wizard.syncPrices.modalTitle'));
        },

        updateButtons() {
            const buttonConfig = [
                {
                    key: 'back',
                    label: this.$tc('sw-first-run-wizard.general.buttonBack'),
                    position: 'left',
                    action: this.routeBackToSyncLibrary,
                    disabled: false
                },
                {
                    key: 'next',
                    label: this.$tc('sw-first-run-wizard.general.buttonNext'),
                    position: 'right',
                    variant: 'primary',
                    action: this.routeToFinish,
                    disabled: false
                }
            ];

            this.$emit('buttons-update', buttonConfig);
        },

        routeBackToSyncLibrary() {
            this.$router.push({
                name: 'swag.paypal.izettle.wizard.syncLibrary',
                params: { id: this.salesChannel.id }
            });
        },

        routeToFinish() {
            this.toggleLoadingState(true);

            // Save the SalesChannel and activate it to mark the setup as complete
            this.saveSalesChannel(true).then(() => {
                this.toggleLoadingState(false);
                this.$router.push({
                    name: 'swag.paypal.izettle.wizard.finish',
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
