import template from './swag-paypal-izettle-wizard-customization.html.twig';

const { Component } = Shopware;

Component.register('swag-paypal-izettle-wizard-customization', {
    template,

    mixins: [
        'placeholder'
    ],

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
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.updateButtons();
            this.setTitle();
            this.resetInputFields();
        },

        setTitle() {
            this.$emit('frw-set-title', this.$tc('swag-paypal-izettle.wizard.customization.modalTitle'));
        },

        updateButtons() {
            const buttonConfig = [
                {
                    key: 'next',
                    label: this.$tc('sw-first-run-wizard.general.buttonNext'),
                    position: 'right',
                    variant: 'primary',
                    action: this.routeToProductSelection,
                    disabled: !(this.salesChannel.name)
                           || !(this.salesChannel.extensions.paypalIZettleSalesChannel.mediaDomain)
                }
            ];

            this.$emit('buttons-update', buttonConfig);
        },

        resetInputFields() {
            this.salesChannel.name = '';
            this.salesChannel.extensions.paypalIZettleSalesChannel.mediaDomain = '';
        },

        routeToProductSelection() {
            this.toggleLoadingState(true);

            this.saveSalesChannel().then(() => {
                this.toggleLoadingState(false);
                this.$router.push({
                    name: 'swag.paypal.izettle.wizard.product-selection',
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
