import template from './swag-paypal-izettle-wizard-locale.html.twig';

const { Component } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('swag-paypal-izettle-wizard-locale', {
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
        currencyCriteria() {
            const criteria = new Criteria();
            criteria.addAssociation('salesChannels');
            if (!this.storefrontSalesChannelId) {
                return criteria;
            }
            return criteria.addFilter(Criteria.equals('salesChannels.id', this.storefrontSalesChannelId));
        },

        domainCriteria() {
            const criteria = new Criteria();
            if (!this.storefrontSalesChannelId) {
                return criteria;
            }
            return criteria.addFilter(Criteria.equals('salesChannelId', this.storefrontSalesChannelId));
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
            this.$emit('frw-set-title', this.$tc('swag-paypal-izettle.wizard.locale.modalTitle'));
        },

        updateButtons() {
            const buttonConfig = [
                {
                    key: 'back',
                    label: this.$tc('sw-first-run-wizard.general.buttonBack'),
                    position: 'left',
                    action: 'swag.paypal.izettle.wizard.sales-channel',
                    disabled: false
                },
                {
                    key: 'next',
                    label: this.$tc('sw-first-run-wizard.general.buttonNext'),
                    position: 'right',
                    variant: 'primary',
                    action: 'swag.paypal.izettle.wizard.product-stream',
                    disabled: !(this.salesChannel.currencyId) ||
                        !(this.salesChannel.extensions.paypalIZettleSalesChannel.salesChannelDomainId)
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
