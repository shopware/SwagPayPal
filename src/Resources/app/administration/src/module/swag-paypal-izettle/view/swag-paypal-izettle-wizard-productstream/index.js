import template from './swag-paypal-izettle-wizard-productstream.html.twig';

const { Component } = Shopware;

Component.register('swag-paypal-izettle-wizard-productstream', {
    template,

    inject: [
        'repositoryFactory'
    ],

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

    computed: {
        productStreamRepository() {
            return this.repositoryFactory.create('product_stream');
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
            this.$emit('frw-set-title', this.$tc('swag-paypal-izettle.wizard.product-stream.modalTitle'));
        },

        updateButtons() {
            const buttonConfig = [
                {
                    key: 'back',
                    label: this.$tc('sw-first-run-wizard.general.buttonBack'),
                    position: 'left',
                    action: 'swag.paypal.izettle.wizard.locale',
                    disabled: false
                },
                {
                    key: 'skip',
                    label: this.$tc('sw-first-run-wizard.general.buttonSkip'),
                    position: 'right',
                    action: 'swag.paypal.izettle.wizard.sync',
                    disabled: !!(this.salesChannel.extensions.paypalIZettleSalesChannel.productStreamId)
                },
                {
                    key: 'next',
                    label: this.$tc('sw-first-run-wizard.general.buttonNext'),
                    position: 'right',
                    variant: 'primary',
                    action: 'swag.paypal.izettle.wizard.sync',
                    disabled: !(this.salesChannel.extensions.paypalIZettleSalesChannel.productStreamId)
                }
            ];

            this.$emit('buttons-update', buttonConfig);
        },

        forceUpdate() {
            this.$forceUpdate();
            this.$nextTick().then(() => {
                this.updateButtons();
            });
        }
    }
});
