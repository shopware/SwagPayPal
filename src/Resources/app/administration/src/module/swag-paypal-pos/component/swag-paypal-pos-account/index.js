import template from './swag-paypal-pos-account.html.twig';
import './swag-paypal-pos-account.scss';

const { Component } = Shopware;

Component.register('swag-paypal-pos-account', {
    template,

    inject: [
        'SwagPayPalPosSettingApiService',
        'repositoryFactory'
    ],

    props: {
        salesChannel: {
            type: Object,
            require: false
        },
        lastRun: {
            type: Object,
            require: false
        }
    },

    data() {
        return {
            isLoading: false,
            merchantInfo: null
        };
    },

    computed: {
        accountName() {
            if (!this.merchantInfo) {
                const firstName = this.$tc('swag-paypal-pos.wizard.connectionSuccess.fakeFirstName');
                const lastName = this.$tc('swag-paypal-pos.wizard.connectionSuccess.fakeLastName');

                return `${firstName} ${lastName}`;
            }

            return this.merchantInfo.name;
        },

        runRepository() {
            return this.repositoryFactory.create('swag_paypal_pos_sales_channel_run');
        }
    },

    created() {
        this.createdComponent();
    },

    watch: {
        salesChannel() {
            this.loadMerchantData().then(() => {
                this.isLoading = false;
            });
        }
    },

    methods: {
        createdComponent() {
            this.loadMerchantData().then(() => {
                this.isLoading = false;
            });
        },

        loadMerchantData() {
            if (this.salesChannel === null) {
                return Promise.resolve();
            }

            return this.SwagPayPalPosSettingApiService.fetchInformation(this.salesChannel)
                .then(({ merchantInformation }) => {
                    this.merchantInfo = merchantInformation;
                });
        }
    }
});
