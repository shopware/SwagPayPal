import template from './swag-paypal-izettle-wizard-sync.html.twig';
import './swag-paypal-izettle-wizard-sync.scss';

const { Component } = Shopware;

Component.register('swag-paypal-izettle-wizard-sync', {
    template,

    props: {
        isLoading: {
            type: Boolean,
            required: true
        },
        salesChannel: {
            type: Object,
            required: true
        },
        storefrontSalesChannelId: {
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
            this.$emit('frw-set-title', this.$tc('swag-paypal-izettle.wizard.sync.modalTitle'));
        },

        updateButtons() {
            const buttonConfig = [
                {
                    key: 'back',
                    label: this.$tc('sw-first-run-wizard.general.buttonBack'),
                    position: 'left',
                    action: 'swag.paypal.izettle.wizard.product-stream',
                    disabled: false
                },
                {
                    key: 'next',
                    label: this.$tc('sw-first-run-wizard.general.buttonNext'),
                    position: 'right',
                    variant: 'primary',
                    action: 'swag.paypal.izettle.wizard.finish',
                    disabled: false
                }
            ];

            this.$emit('buttons-update', buttonConfig);
        },

        forceUpdate() {
            this.$forceUpdate();
        }
    }
});
