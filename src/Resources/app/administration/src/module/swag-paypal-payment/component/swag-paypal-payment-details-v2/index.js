import template from './swag-paypal-payment-details-v2.html.twig';
import {
    ORDER_AUTHORIZATION_CREATED,
    ORDER_AUTHORIZATION_PARTIALLY_CAPTURED,
    ORDER_AUTHORIZATION_PENDING,
} from './swag-paypal-order-consts';

const { Component, Filter } = Shopware;

Component.register('swag-paypal-payment-details-v2', {
    template,

    props: {
        paypalOrder: {
            type: Object,
            required: true,
        },

        orderTransaction: {
            type: Object,
            required: true,
        },
    },

    data() {
        return {
            payments: [],
            createDateTime: '',
            updateDateTime: '',
            currency: '',
            amount: {},
            payerId: '',
            refundableAmount: 0,
            captureableAmount: 0,
            showVoidButton: false,
        };
    },

    computed: {
        dateFilter() {
            return Filter.getByName('date');
        },

        paymentColumns() {
            return [
                {
                    property: 'type',
                    label: this.$tc('swag-paypal-payment.transactionHistory.types.type'),
                    rawData: true,
                },
                {
                    property: 'id',
                    label: this.$tc('swag-paypal-payment.transactionHistory.types.trackingId'),
                    rawData: true,
                },
                {
                    property: 'total',
                    label: this.$tc('swag-paypal-payment.transactionHistory.types.amount'),
                    rawData: true,
                },
                {
                    property: 'status',
                    label: this.$tc('swag-paypal-payment.transactionHistory.types.state'),
                    rawData: true,
                },
                {
                    property: 'transactionFee',
                    label: this.$tc('swag-paypal-payment.transactionHistory.types.transactionFee'),
                    rawData: true,
                },
                {
                    property: 'create',
                    label: this.$tc('swag-paypal-payment.transactionHistory.types.createTime'),
                    rawData: true,
                },
                {
                    property: 'update',
                    label: this.$tc('swag-paypal-payment.transactionHistory.types.updateTime'),
                    rawData: true,
                },
            ];
        },

        puiDetails() {
            return this.orderTransaction.customFields.swag_paypal_pui_payment_instruction;
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.createDateTime = this.formatDate(this.paypalOrder.create_time);
            this.updateDateTime = this.formatDate(this.paypalOrder.update_time);
            this.amount = this.paypalOrder.purchase_units[0].amount;
            this.currency = this.amount.currency_code;
            this.payerId = this.paypalOrder.payer?.payer_id ?? '';

            this.setPayments();
        },

        setPayments() {
            const payments = this.paypalOrder.purchase_units[0].payments;
            if (payments === null) {
                return;
            }

            const rawAuthorizations = payments.authorizations;
            const rawCaptures = payments.captures;
            const rawRefunds = payments.refunds;

            if (rawAuthorizations !== null) {
                rawAuthorizations.forEach((authorization) => {
                    this.pushPayment('authorization', authorization);
                    const authStatus = authorization.status;
                    if (authStatus === ORDER_AUTHORIZATION_CREATED
                        || authStatus === ORDER_AUTHORIZATION_PARTIALLY_CAPTURED
                    ) {
                        this.captureableAmount += Number(authorization.amount.value);
                        this.showVoidButton = true;
                    }
                    if (authStatus === ORDER_AUTHORIZATION_PENDING) {
                        this.showVoidButton = true;
                    }
                });
            }

            if (rawCaptures !== null) {
                rawCaptures.forEach((capture) => {
                    this.pushPayment('capture', capture);
                    const captureAmount = Number(capture.amount.value);
                    this.refundableAmount += captureAmount;
                    this.captureableAmount -= captureAmount;
                });
            }

            if (rawRefunds !== null) {
                rawRefunds.forEach((refund) => {
                    this.pushPayment('refund', refund);
                    this.refundableAmount -= Number(refund.amount.value);
                });
            }

            this.refundableAmount = this.formatAmount(this.refundableAmount);
            this.captureableAmount = this.formatAmount(this.captureableAmount);
        },

        pushPayment(type, payment) {
            this.payments.push({
                id: payment.id,
                type: this.$tc(`swag-paypal-payment.transactionHistory.states.${type}`),
                total: `${payment.amount.value} ${payment.amount.currency_code}`,
                create: this.formatDate(payment.create_time),
                createRaw: payment.create_time,
                update: this.formatDate(payment.update_time),
                transactionFee: this.getTransactionFee(type, payment),
                status: payment.status,
            });

            this.payments.sort((a, b) => {
                const dateA = new Date(a.createRaw);
                const dateB = new Date(b.createRaw);

                return dateA - dateB;
            });
        },

        getTransactionFee(type, payment) {
            if (type === 'capture') {
                const sellerReceivableBreakdown = payment.seller_receivable_breakdown;
                if (sellerReceivableBreakdown === null) {
                    return null;
                }

                const paypalFee = sellerReceivableBreakdown.paypal_fee;
                if (paypalFee == null) {
                    return null;
                }

                return `${paypalFee.value} ${paypalFee.currency_code}`;
            }

            if (type === 'refund') {
                const sellerPayableBreakdown = payment.seller_payable_breakdown;
                if (sellerPayableBreakdown === null) {
                    return null;
                }

                const paypalFee = sellerPayableBreakdown.paypal_fee;
                if (paypalFee === null) {
                    return null;
                }

                return `${paypalFee.value} ${paypalFee.currency_code}`;
            }

            return null;
        },

        formatDate(dateTime) {
            return this.dateFilter(dateTime, {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
            });
        },

        formatAmount(value) {
            return Number(`${Math.round(`${value}e2`)}e-2`);
        },
    },
});
