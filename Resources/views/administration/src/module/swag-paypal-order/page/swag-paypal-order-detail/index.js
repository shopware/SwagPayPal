import { Component, Filter, Mixin, State } from 'src/core/shopware';
import template from './swag-paypal-order-detail.html.twig';
import './swag-paypal-order-detail.scss';

Component.register('swag-paypal-order-detail', {
    template,

    inject: ['SwagPayPalPaymentService'],

    mixins: [
        Mixin.getByName('notification')
    ],

    data() {
        return {
            paymentResource: {},
            isLoading: true,
            createDateTime: '',
            updateDateTime: '',
            currency: '',
            amount: {}
        };
    },

    computed: {
        dateFilter() {
            return Filter.getByName('date');
        },
        orderStore() {
            return State.getStore('order');
        }
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            const orderId = this.$route.params.id;

            this.orderStore.getByIdAsync(orderId).then((order) => {
                order.getAssociation('transactions').getList({ page: 1, limit: 1 }).then((orderTransactions) => {
                    const paypalPaymentId = orderTransactions.items[0].details.swag_paypal.transactionId;

                    this.SwagPayPalPaymentService.getPaymentDetails(paypalPaymentId).then((payment) => {
                        this.paymentResource = payment;
                        this.createDateTime = this.formatDate(this.paymentResource.create_time);
                        this.updateDateTime = this.formatDate(this.paymentResource.update_time);
                        this.currency = this.paymentResource.transactions[0].amount.currency;
                        this.amount = this.paymentResource.transactions[0].amount;
                        this.isLoading = false;
                    }).catch((errorResponse) => {
                        this.createNotificationError({
                            message: errorResponse.message,
                            title: this.$tc('swag-paypal.orderDetailTab.error.title'),
                            autoClose: false
                        });
                        this.isLoading = false;
                    });
                });
            });
        },

        formatDate(dateTime) {
            return this.dateFilter(dateTime, {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
        }
    }
});
