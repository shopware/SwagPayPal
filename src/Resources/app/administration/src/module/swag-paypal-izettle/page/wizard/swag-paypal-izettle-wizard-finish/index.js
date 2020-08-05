import template from './swag-paypal-izettle-wizard-finish.html.twig';
import './swag-paypal-izettle-wizard-finish.scss';

const { Component } = Shopware;

Component.register('swag-paypal-izettle-wizard-finish', {
    template,

    props: {
        salesChannel: {
            type: Object,
            required: true
        },
        cloneSalesChannelId: {
            type: String,
            required: false
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
            this.$emit('frw-set-title', this.$tc('swag-paypal-izettle.wizard.finish.modalTitle'));
        },

        updateButtons() {
            const buttonConfig = [
                {
                    key: 'back',
                    label: this.$tc('sw-first-run-wizard.general.buttonBack'),
                    position: 'left',
                    action: this.routeBackToSync,
                    disabled: false
                },
                {
                    key: 'finish',
                    label: this.$tc('sw-first-run-wizard.general.buttonFinish'),
                    position: 'right',
                    variant: 'primary',
                    action: this.onFinish,
                    disabled: false
                }
            ];

            this.$emit('buttons-update', buttonConfig);
        },

        routeBackToSync() {
            this.$router.push({
                name: 'swag.paypal.izettle.wizard.sync',
                params: { id: this.salesChannel.id }
            });
        },

        onFinish() {
            this.$emit('frw-finish');
        }
    }
});
