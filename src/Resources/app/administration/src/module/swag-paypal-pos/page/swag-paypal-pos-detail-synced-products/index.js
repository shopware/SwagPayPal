import template from './swag-paypal-pos-detail-synced-products.html.twig';
import './swag-paypal-pos-detail-synced-products.scss';

const { Component } = Shopware;

Component.register('swag-paypal-pos-detail-synced-products', {
    template,

    inject: [
        'SwagPayPalPosApiService',
    ],

    mixins: [
        Shopware.Mixin.getByName('swag-paypal-pos-log-label'),
        Shopware.Mixin.getByName('listing'),
    ],

    props: {
        salesChannel: {
            type: Object,
            required: true,
        },
    },

    data() {
        return {
            products: [],
            limit: 10,
            isLoading: false,
            actions: [
                {
                    label: 'swag-paypal-pos.detail.syncedProducts.actions.productDetails',
                    callback: this.onProductDetails,
                },
            ],
            columns: [
                {
                    property: 'name',
                    label: 'swag-paypal-pos.detail.syncedProducts.columns.name',
                    sortable: false,
                },
                {
                    property: 'state',
                    label: 'swag-paypal-pos.detail.syncedProducts.columns.state',
                    sortable: false,
                },
                {
                    property: 'date',
                    label: 'swag-paypal-pos.detail.syncedProducts.columns.date',
                    sortable: false,
                },
            ],
        };
    },

    created() {
        this.createdComponent();
    },

    computed: {
        dateFilter() {
            return Shopware.Filter.getByName('date');
        },
    },

    methods: {
        createdComponent() {
            this.$emit('buttons-update', []);
            this.getList();
        },

        getList() {
            if (this.salesChannel === null || this.salesChannel.id === null) {
                return Promise.resolve();
            }

            this.isLoading = true;
            const params = this.getMainListingParams();

            return this.SwagPayPalPosApiService.getProductLog(
                this.salesChannel.id,
                params.page,
                params.limit,
            ).then((result) => {
                this.products = Object.values(result.elements);
                this.total = result.total;
                this.isLoading = false;
            });
        },

        onProductDetails(item) {
            this.$router.push(
                {
                    name: 'sw.product.detail.base',
                    params: {
                        id: item.id,
                    },
                },
            );
        },

        hasSync(item) {
            return item.extensions.paypalPosLog.length || item.extensions.paypalPosSync.length;
        },

        getSyncDate(item) {
            if (!this.hasSync(item)) {
                return null;
            }

            if (item.extensions.paypalPosLog[0]) {
                return item.extensions.paypalPosLog[0].posSalesChannelRun.updatedAt
                    || item.extensions.paypalPosLog[0].createdAt;
            }

            return item.extensions.paypalPosSync[0].updatedAt
                || item.extensions.paypalPosSync[0].createdAt;
        },

        getLevel(item) {
            return item.extensions.paypalPosLog[0] ? item.extensions.paypalPosLog[0].level : 200;
        },
    },
});
