import { Filter, Mixin, State } from 'src/core/shopware';
import template from './swag-paypal-payment-detail.html.twig';
import './swag-paypal-payment-detail.scss';

export default {
    name: 'swag-paypal-payment-detail',

    template,

    inject: ['SwagPayPalPaymentService'],

    mixins: [
        Mixin.getByName('notification')
    ],

    watch: {
        '$route'() {
            this.resetDataAttributes();
            this.createdComponent();
        }
    },

    data() {
        return {
            paymentResource: {},
            relatedResources: [],
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
        },
        relatedResourceColumns() {
            return [
                {
                    property: 'type',
                    label: this.$tc('swag-paypal-payment.transactionHistory.types.type'),
                    rawData: true
                },
                {
                    property: 'total',
                    label: this.$tc('swag-paypal-payment.transactionHistory.types.amount'),
                    rawData: true
                },
                {
                    property: 'id',
                    label: this.$tc('swag-paypal-payment.transactionHistory.types.trackingId'),
                    rawData: true
                },
                {
                    property: 'status',
                    label: this.$tc('swag-paypal-payment.transactionHistory.types.state'),
                    rawData: true
                },
                {
                    property: 'transactionFee',
                    label: this.$tc('swag-paypal-payment.transactionHistory.types.transactionFee'),
                    rawData: true
                },
                {
                    property: 'paymentMode',
                    label: this.$tc('swag-paypal-payment.transactionHistory.types.paymentMode'),
                    rawData: true
                },
                {
                    property: 'create',
                    label: this.$tc('swag-paypal-payment.transactionHistory.types.createTime'),
                    rawData: true
                },
                {
                    property: 'update',
                    label: this.$tc('swag-paypal-payment.transactionHistory.types.updateTime'),
                    rawData: true
                }
            ];
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
                    const paypalPaymentId = orderTransactions.items[0].attributes.swag_paypal_transaction_id;
                    this.SwagPayPalPaymentService.getPaymentDetails(paypalPaymentId).then((payment) => {
                        this.paymentResource = payment;
                        this.setRelatedResources();
                        this.createDateTime = this.formatDate(this.paymentResource.create_time);
                        this.updateDateTime = this.formatDate(this.paymentResource.update_time);
                        this.currency = this.paymentResource.transactions[0].amount.currency;
                        this.amount = this.paymentResource.transactions[0].amount;
                        this.isLoading = false;
                    }).catch((errorResponse) => {
                        this.createNotificationError({
                            title: this.$tc('swag-paypal-payment.paymentDetails.error.title'),
                            message: errorResponse.message,
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
        },

        setRelatedResources() {
            const rawRelatedResources = this.paymentResource.transactions[0].related_resources;

            rawRelatedResources.forEach((relatedResource) => {
                if (relatedResource.sale) {
                    this.pushRelatedResource('sale', relatedResource.sale);
                }

                if (relatedResource.authorization) {
                    this.pushRelatedResource('authorization', relatedResource.authorization);
                }

                if (relatedResource.order) {
                    this.pushRelatedResource('order', relatedResource.order);
                }

                if (relatedResource.refund) {
                    this.pushRelatedResource('refund', relatedResource.refund);
                }

                if (relatedResource.capture) {
                    this.pushRelatedResource('capture', relatedResource.capture);
                }
            });
        },

        pushRelatedResource(type, relatedResource) {
            let transactionFee = null;
            const currency = relatedResource.amount.currency;
            if (relatedResource.transaction_fee) {
                transactionFee = `${relatedResource.transaction_fee.value} ${currency}`;
            }

            this.relatedResources.push({
                id: relatedResource.id,
                type: this.$tc(`swag-paypal-payment.transactionHistory.states.${type}`),
                total: `${relatedResource.amount.total} ${currency}`,
                create: this.formatDate(relatedResource.create_time),
                update: this.formatDate(relatedResource.update_time),
                transactionFee: transactionFee,
                status: relatedResource.state,
                paymentMode: relatedResource.payment_mode
            });
        },

        resetDataAttributes() {
            this.paymentResource = {};
            this.relatedResources = [];
            this.isLoading = true;
            this.createDateTime = '';
            this.updateDateTime = '';
            this.currency = '';
            this.amount = {};
        }
    }
};
