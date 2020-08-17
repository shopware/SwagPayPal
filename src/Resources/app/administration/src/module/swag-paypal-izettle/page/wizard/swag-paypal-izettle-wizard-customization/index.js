import template from './swag-paypal-izettle-wizard-customization.html.twig';
import './swag-paypal-izettle-wizard-customization.scss';

const { Component } = Shopware;

Component.register('swag-paypal-izettle-wizard-customization', {
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
        },
        isLoading: {
            type: Boolean,
            required: false,
            default() {
                return false;
            }
        }
    },

    watch: {
        'isLoading'(loading) {
            if (loading) {
                return;
            }

            this.updateButtons();
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
            this.$emit('frw-set-title', this.$tc('swag-paypal-izettle.wizard.customization.modalTitle'));
        },

        updateButtons() {
            const buttonConfig = [
                {
                    key: 'back',
                    label: this.$tc('sw-first-run-wizard.general.buttonBack'),
                    position: 'left',
                    action: this.routeBackToConnectionSuccess,
                    disabled: false
                },
                {
                    key: 'next',
                    label: this.$tc('sw-first-run-wizard.general.buttonNext'),
                    position: 'right',
                    variant: 'primary',
                    action: this.routeToProductSelection,
                    disabled: this.nextButtonDisabled()
                }
            ];

            this.$emit('buttons-update', buttonConfig);
        },

        nextButtonDisabled() {
            return this.isLoading
                || !(this.salesChannel.name)
                || !(this.salesChannel.extensions.paypalIZettleSalesChannel.mediaDomain);
        },

        routeBackToConnectionSuccess() {
            this.$router.push({
                name: 'swag.paypal.izettle.wizard.connectionSuccess',
                params: { id: this.salesChannel.id }
            });
        },

        routeToProductSelection() {
            this.toggleLoadingState(true);

            this.saveSalesChannel().then(() => {
                this.toggleLoadingState(false);
                this.$router.push({
                    name: 'swag.paypal.izettle.wizard.productSelection',
                    params: { id: this.salesChannel.id }
                });
            }).finally(() => {
                this.toggleLoadingState(false);
            });
        },

        forceUpdate() {
            this.$forceUpdate();
            this.$nextTick().then(() => {
                this.updateButtons();
            });
        },

        toggleLoadingState(state) {
            this.isConnecting = state;
            this.$emit('toggle-loading', state);
        }
    }
});
