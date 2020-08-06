import template from './swag-paypal-izettle-wizard-sync-library.html.twig';
import './swag-paypal-izettle-wizard-sync-library.scss';

const { Component } = Shopware;

Component.register('swag-paypal-izettle-wizard-sync-library', {
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
                name: this.$tc('swag-paypal-izettle.wizard.sync-library.optionTrueLabel'),
                description: this.$tc('swag-paypal-izettle.wizard.sync-library.optionTrueDescription')
            };
        },

        optionFalse() {
            return {
                name: this.$tc('swag-paypal-izettle.wizard.sync-library.optionFalseLabel'),
                description: this.$tc('swag-paypal-izettle.wizard.sync-library.optionFalseDescription')
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
            this.$emit('frw-set-title', this.$tc('swag-paypal-izettle.wizard.sync-library.modalTitle'));
        },

        updateButtons() {
            const buttonConfig = [
                {
                    key: 'back',
                    label: this.$tc('sw-first-run-wizard.general.buttonBack'),
                    position: 'left',
                    action: this.routeBackToSyncPrices,
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

        routeBackToSyncPrices() {
            this.$router.push({
                name: 'swag.paypal.izettle.wizard.sync-prices',
                params: { id: this.salesChannel.id }
            });
        },

        routeToFinish() {
            this.toggleLoadingState(true);

            this.saveSalesChannel().then(() => {
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
