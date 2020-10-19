import template from './swag-paypal-payment-detail.html.twig';
import './swag-paypal-payment-detail.scss';

const { Component, Filter, Context } = Shopware;
const { isEmpty } = Shopware.Utils.types;
const Criteria = Shopware.Data.Criteria;

Component.register('swag-paypal-payment-detail', {
    template,

    inject: [
        'SwagPayPalPaymentService',
        'SwagPayPalOrderService',
        'repositoryFactory'
    ],

    mixins: ['notification'],

    data() {
        return {
            order: {},
            orderTransaction: {},
            paypalOrder: {},
            /**
             * @deprecated tag:v3.0.0 - Will be removed. Only used for old orders done with "Payments-v1"-API
             */
            paymentResource: {},
            isLoading: true,
            orderTransactionState: null,
            invalidResourceError: false
        };
    },

    computed: {
        dateFilter() {
            return Filter.getByName('date');
        },

        showCanceledPaymentError() {
            return this.isLoading === false
                && this.showPayPalPayment === false
                && this.showPayPalOrder === false
                && this.orderTransactionState === 'failed';
        },

        showSandboxLiveError() {
            return this.isLoading === false
                && this.showPayPalPayment === false
                && this.showPayPalOrder === false
                && this.invalidResourceError === true
                && this.orderTransactionState !== 'failed';
        },

        showPayPalPayment() {
            return isEmpty(this.paymentResource) === false;
        },

        showPayPalOrder() {
            return isEmpty(this.paypalOrder) === false;
        }
    },

    watch: {
        '$route'() {
            this.createdComponent();
        },

        'order.orderNumber'() {
            this.emitIdentifier();
        }
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            const orderId = this.$route.params.id;
            const orderRepository = this.repositoryFactory.create('order');
            const orderCriteria = new Criteria(1, 1);
            orderCriteria.addAssociation('transactions.stateMachineState');
            orderCriteria.getAssociation('transactions').addSorting(Criteria.sort('createdAt'));

            orderRepository.get(orderId, Context.api, orderCriteria).then((order) => {
                this.order = order;
                this.orderTransaction = order.transactions[order.transactions.length - 1];
                this.orderTransactionState = this.orderTransaction.stateMachineState.technicalName;

                if (this.orderTransaction.customFields === null) {
                    this.isLoading = false;

                    return;
                }

                const paypalPaymentId = this.orderTransaction.customFields.swag_paypal_transaction_id;
                if (paypalPaymentId) {
                    this.handlePayPalPayment(paypalPaymentId);
                }
                const paypalOrderId = this.orderTransaction.customFields.swag_paypal_order_id;
                if (!paypalOrderId) {
                    this.isLoading = false;

                    return;
                }

                this.handlePayPalOrder(paypalOrderId);
            });
        },

        handlePayPalOrder(paypalOrderId) {
            this.SwagPayPalOrderService.getOrderDetails(this.orderTransaction.id, paypalOrderId).then((paypalOrder) => {
                this.paypalOrder = paypalOrder;
                this.isLoading = false;
            }).catch(this.handleError);
        },

        /**
         * @deprecated tag:v3.0.0 - Will be removed. Only used for old orders done with "Payments-v1"-API
         */
        handlePayPalPayment(paypalPaymentId) {
            this.SwagPayPalPaymentService.getPaymentDetails(this.order.id, paypalPaymentId).then((payment) => {
                this.paymentResource = payment;
                this.isLoading = false;
            }).catch(this.handleError);
        },

        handleError(errorResponse) {
            if (errorResponse.response.data.errors[0].meta.parameters.name
                && errorResponse.response.data.errors[0].meta.parameters.name === 'INVALID_RESOURCE_ID'
            ) {
                this.invalidResourceError = true;
                this.isLoading = false;

                return;
            }

            try {
                this.createNotificationError({
                    title: this.$tc('swag-paypal-payment.paymentDetails.error.title'),
                    message: errorResponse.response.data.errors[0].detail,
                    autoClose: false
                });
            } catch (e) {
                this.createNotificationError({
                    title: this.$tc('swag-paypal-payment.paymentDetails.error.title'),
                    message: errorResponse.message,
                    autoClose: false
                });
            } finally {
                this.isLoading = false;
            }
        },

        emitIdentifier() {
            const orderNumber = this.order !== null ? this.order.orderNumber : '';
            this.$emit('identifier-change', orderNumber);
        }
    }
});
