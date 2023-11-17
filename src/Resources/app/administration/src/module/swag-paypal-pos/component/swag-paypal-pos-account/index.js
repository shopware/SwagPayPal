import template from './swag-paypal-pos-account.html.twig';
import './swag-paypal-pos-account.scss';

const { Component } = Shopware;

Component.register('swag-paypal-pos-account', {
    template,

    inject: [
        'SwagPayPalPosSettingApiService',
        'repositoryFactory',
    ],

    props: {
        salesChannel: {
            type: Object,
            require: false,
            default: null,
        },
        lastRun: {
            type: Object,
            require: false,
            default: null,
        },
    },

    data() {
        return {
            isLoading: false,
            isError: false,
            merchantInfo: null,
        };
    },

    computed: {
        accountName() {
            if (this.isError) {
                return this.$tc('swag-paypal-pos.account.errorName');
            }

            if (!this.merchantInfo) {
                return this.$tc('swag-paypal-pos.account.loadingName');
            }

            return this.merchantInfo.name;
        },

        accountEmail() {
            if (this.isError) {
                return this.$tc('swag-paypal-pos.account.errorEmail');
            }

            if (!this.merchantInfo) {
                return this.$tc('swag-paypal-pos.account.loadingEmail');
            }

            return this.merchantInfo.contactEmail;
        },

        connectionStatusText() {
            if (this.isError) {
                return this.$tc('swag-paypal-pos.account.noConnectionStatus');
            }

            return this.$tc('swag-paypal-pos.account.connectedStatus');
        },

        connectionStatusVariant() {
            if (this.isError) {
                return 'danger';
            }

            return 'success';
        },

        runRepository() {
            return this.repositoryFactory.create('swag_paypal_pos_sales_channel_run');
        },

        assetFilter() {
            return Shopware.Filter.getByName('asset');
        },

        dateFilter() {
            return Shopware.Filter.getByName('date');
        },
    },

    watch: {
        salesChannel() {
            this.loadMerchantData();
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.loadMerchantData();
        },

        loadMerchantData() {
            this.isError = false;
            this.isLoading = true;

            if (this.salesChannel === null) {
                return Promise.resolve();
            }

            return this.SwagPayPalPosSettingApiService.fetchInformation(this.salesChannel)
                .then(({ merchantInformation }) => {
                    this.merchantInfo = merchantInformation;
                    this.isError = false;
                }).catch(() => {
                    this.merchantInfo = null;
                    this.isError = true;
                }).finally(() => {
                    this.isLoading = false;
                });
        },
    },
});
