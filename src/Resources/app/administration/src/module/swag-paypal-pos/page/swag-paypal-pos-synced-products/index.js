import template from './swag-paypal-pos-synced-products.html.twig';
import './swag-paypal-pos-synced-products.scss';

const { Component } = Shopware;

Component.register('swag-paypal-pos-synced-products', {
    template,

    inject: [
        'SwagPayPalPosApiService'
    ],

    mixins: [
        'swag-paypal-pos-log-label',
        'listing'
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
            limit: 10,
            isLoading: false,
            actions: [
                {
                    label: 'swag-paypal-pos.detail.syncedProducts.actions.productDetails',
                    callback: this.onProductDetails
                }
            ],
            columns: [
                {
                    property: 'name',
                    label: 'swag-paypal-pos.detail.syncedProducts.columns.name',
                    sortable: false
                },
                {
                    property: 'state',
                    label: 'swag-paypal-pos.detail.syncedProducts.columns.state',
                    sortable: false
                },
                {
                    property: 'date',
                    label: 'swag-paypal-pos.detail.syncedProducts.columns.date',
                    sortable: false
                }
            ]
        };
    },

    created() {
        this.createdComponent();
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
            const params = this.getListingParams();

            return this.SwagPayPalPosApiService.getProductLog(
                this.salesChannel.id,
                params.page,
                params.limit
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
                        id: item.id
                    }
                }
            );
        }
    }
});
