import template from './swag-paypal-izettle-synced-products.html.twig';

const { Component } = Shopware;

Component.register('swag-paypal-izettle-synced-products', {
    template,

    inject: [
        'SwagPayPalIZettleApiService'
    ],

    mixins: [
        'swag-paypal-izettle-log-label'
    ],

    props: {
        salesChannel: {
            type: Object,
            required: true
        }
    },

    data() {
        return {
            products: [],
            page: 1,
            limit: 10,
            total: 0,
            isLoading: false,
            actions: [
                {
                    label: 'swag-paypal-izettle.detail.syncedProducts.actions.productDetails',
                    callback: this.onProductDetails
                }
            ],
            columns: [
                { property: 'name', label: 'swag-paypal-izettle.detail.syncedProducts.columns.name', sortable: false },
                { property: 'state', label: 'swag-paypal-izettle.detail.syncedProducts.columns.state', sortable: false },
                { property: 'date', label: 'swag-paypal-izettle.detail.syncedProducts.columns.date', sortable: false }
            ]
        };
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.fetchProducts();
        },

        fetchProducts() {
            if (this.salesChannel === null || this.salesChannel.id === null) {
                return Promise.resolve();
            }

            this.isLoading = true;
            return this.SwagPayPalIZettleApiService.getProductLog(
                this.salesChannel.id,
                this.page,
                this.limit
            ).then((result) => {
                this.products = Object.values(result.elements);
                this.total = result.total;
                this.isLoading = false;
            });
        },

        onPaginateProducts({ page = 1, limit = 10 }) {
            this.page = page;
            this.limit = limit;

            return this.fetchProducts();
        },

        onProductDetails(item) {
            this.$router.push(
                {
                    name: 'sw.product.detail.base',
                    params: {
                        id: item.id
                    }
                }
            );
        }
    }
});
