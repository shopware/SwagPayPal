import template from './swag-paypal-izettle-wizard-saleschannel.html.twig';

const { Component, Defaults } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('swag-paypal-izettle-wizard-saleschannel', {
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
        storefrontSalesChannelCriteria() {
            const criteria = new Criteria();

            return criteria.addFilter(Criteria.equals('typeId', Defaults.storefrontSalesChannelTypeId));
        },

        salesChannelRepository() {
            return this.repositoryFactory.create('sales_channel');
        },

        localStorefrontSalesChannelId: {
            get() {
                this.updateButtons();
                return this.storefrontSalesChannelId;
            },
            set(storefrontSalesChannelId) {
                this.$emit('update-storefront-sales-channel', storefrontSalesChannelId);
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
            this.$emit('frw-set-title', this.$tc('swag-paypal-izettle.wizard.sales-channel.modalTitle'));
        },

        updateButtons() {
            const buttonConfig = [
                {
                    key: 'back',
                    label: this.$tc('sw-first-run-wizard.general.buttonBack'),
                    position: 'left',
                    action: 'swag.paypal.izettle.wizard.connection',
                    disabled: false
                },
                {
                    key: 'next',
                    label: this.$tc('sw-first-run-wizard.general.buttonNext'),
                    position: 'right',
                    variant: 'primary',
                    action: 'swag.paypal.izettle.wizard.locale',
                    disabled: !(this.storefrontSalesChannelId)
                }
            ];

            this.$emit('buttons-update', buttonConfig);
        },

        forceUpdate() {
            this.$forceUpdate();
            this.updateButtons();
        }
    }
});
