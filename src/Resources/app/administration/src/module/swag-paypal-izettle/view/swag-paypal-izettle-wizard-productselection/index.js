import template from './swag-paypal-izettle-wizard-productselection.html.twig';

const { Component } = Shopware;

Component.register('swag-paypal-izettle-wizard-productselection', {
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
        cloneSalesChannelId: {
            type: String,
            required: false
        }
    },

    data() {
        return {
            manualSalesChannel: false,
            hasClone: false
        };
    },

    computed: {
        salesChannelRepository() {
            return this.repositoryFactory.create('sales_channel');
        },

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
                    action: 'swag.paypal.izettle.wizard.customization',
                    disabled: false
                },
                {
                    key: 'next',
                    label: this.$tc('sw-first-run-wizard.general.buttonNext'),
                    position: 'right',
                    variant: 'primary',
                    action: 'swag.paypal.izettle.wizard.product-stream',
                    disabled: false
                }
            ];

            this.$emit('buttons-update', buttonConfig);
        },

        forceUpdate() {
            this.$forceUpdate();
            this.updateButtons();
        },

        updateClone() {
            this.$emit('update-clone-sales-channel', null);
            this.forceUpdate();
        }
    }
});
