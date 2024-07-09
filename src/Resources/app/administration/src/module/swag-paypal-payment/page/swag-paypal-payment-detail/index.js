import template from './swag-paypal-payment-detail.html.twig';
import './swag-paypal-payment-detail.scss';

const { Component, Filter } = Shopware;
const { isEmpty } = Shopware.Utils.types;
const { mapState } = Component.getComponentHelper();

Component.register('swag-paypal-payment-detail', {
    template,

    inject: [
        'SwagPayPalPaymentService',
        'SwagPayPalOrderService',
        'repositoryFactory',
    ],

    mixins: [
        Shopware.Mixin.getByName('notification'),
    ],

    props: {
        isLoading: {
            type: Boolean,
            required: true,
        },
    },

    data() {
        return {
            loading: false,
            paypalOrder: null,
            paymentResource: null,
        };
    },

    computed: {
        ...mapState('swOrderDetail', ['order']),

        orderTransaction() {
            return this.order.transactions.last();
        },

        dateFilter() {
            return Filter.getByName('date');
        },

        stateFailedCancelled() {
            return this.orderTransaction.stateMachineState.technicalName in ['cancelled', 'failed'];
        },

        hasPayPalDetails() {
            return !!this.paypalOrder || !!this.paymentResource;
        },
    },

    watch: {
        order: {
            immediate: true,
            handler() {
                this.paypalOrder = null;
                this.paymentResource = null;

                this.fetchPayPalDetails();
            },
        },
    },

    methods: {
        async fetchPayPalDetails() {
            if (!this.order || isEmpty(this.orderTransaction.customFields)) {
                return;
            }

            this.loading = true;

            const paypalPaymentId = this.orderTransaction.customFields.swag_paypal_transaction_id;
            if (paypalPaymentId) {
                await this.handlePayPalPayment(paypalPaymentId);
            }

            const paypalOrderId = this.orderTransaction.customFields.swag_paypal_order_id;
            if (paypalOrderId) {
                await this.handlePayPalOrder(paypalOrderId);
            }
        },

        handlePayPalOrder(paypalOrderId) {
            return this.SwagPayPalOrderService.getOrderDetails(this.orderTransaction.id, paypalOrderId)
                .then((paypalOrder) => {
                    this.paypalOrder = paypalOrder;
                    this.loading = false;
                }).catch(this.handleError);
        },

        handlePayPalPayment(paypalPaymentId) {
            return this.SwagPayPalPaymentService.getPaymentDetails(this.order.id, paypalPaymentId)
                .then((payment) => {
                    this.paymentResource = payment;
                    this.loading = false;
                }).catch(this.handleError);
        },

        handleError(errorResponse) {
            try {
                this.createNotificationError({
                    message: `${this.$tc('swag-paypal-payment.paymentDetails.error.title')}: ${
                        errorResponse.response.data.errors[0].detail}`,
                    autoClose: false,
                });
            } catch (e) {
                this.createNotificationError({
                    message: `${this.$tc('swag-paypal-payment.paymentDetails.error.title')}: ${errorResponse.message}`,
                    autoClose: false,
                });
            } finally {
                this.loading = false;
            }
        },
    },
});
