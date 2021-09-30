import template from './swag-paypal-pos-wizard-finish.html.twig';
import './swag-paypal-pos-wizard-finish.scss';

const { Component } = Shopware;

Component.register('swag-paypal-pos-wizard-finish', {
    template,

    inject: [
        'SwagPayPalPosApiService',
    ],

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
            this.$emit('frw-set-title', this.$tc('swag-paypal-pos.wizard.finish.modalTitle'));
        },

        updateButtons() {
            const buttonConfig = [
                {
                    key: 'back',
                    label: this.$tc('sw-first-run-wizard.general.buttonBack'),
                    position: 'left',
                    action: this.routeBackToSyncPrices,
                    disabled: false,
                },
                {
                    key: 'finish',
                    label: this.$tc('sw-first-run-wizard.general.buttonFinish'),
                    position: 'right',
                    variant: 'primary',
                    action: this.onFinish,
                    disabled: false,
                },
            ];

            this.$emit('buttons-update', buttonConfig);
        },

        routeBackToSyncPrices() {
            this.$router.push({
                name: 'swag.paypal.pos.wizard.syncPrices',
                params: { id: this.salesChannel.id },
            });
        },

        onFinish() {
            this.$emit('frw-finish');
        },
    },
});
