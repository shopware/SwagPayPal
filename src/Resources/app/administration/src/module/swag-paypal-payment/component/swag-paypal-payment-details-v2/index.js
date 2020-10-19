import template from './swag-paypal-payment-details-v2.html.twig';

const { Component, Filter } = Shopware;

Component.register('swag-paypal-payment-details-v2', {
    template,

    props: {
        paypalOrder: {
            type: Object,
            required: true
        },

        orderId: {
            type: String,
            required: true
        }
    },

    data() {
        return {
            payments: [],
            createDateTime: '',
            updateDateTime: '',
            currency: '',
            amount: {},
            payerId: ''
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
                    rawData: true
                },
                {
                    property: 'total',
                    label: this.$tc('swag-paypal-payment.transactionHistory.types.amount'),
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
            this.createDateTime = this.formatDate(this.paypalOrder.create_time);
            this.updateDateTime = this.formatDate(this.paypalOrder.update_time);
            this.amount = this.paypalOrder.purchase_units[0].amount;
            this.currency = this.paypalOrder.purchase_units[0].amount.currency_code;
            this.payerId = this.paypalOrder.payer.payer_id;

            this.setPayments();
        },

        setPayments() {
            const rawAuthorizations = this.paypalOrder.purchase_units[0].payments.authorizations;
            const rawCaptures = this.paypalOrder.purchase_units[0].payments.captures;
            const rawRefunds = this.paypalOrder.purchase_units[0].payments.refunds;

            if (rawAuthorizations !== null) {
                rawAuthorizations.forEach((authorization) => {
                    this.pushPayment('authorization', authorization);
                });
            }

            if (rawCaptures !== null) {
                rawCaptures.forEach((capture) => {
                    this.pushPayment('capture', capture);
                });
            }

            if (rawRefunds !== null) {
                rawRefunds.forEach((refund) => {
                    this.pushPayment('refund', refund);
                });
            }
        },

        pushPayment(type, payment) {
            let transactionFee = null;
            if (payment.seller_receivable_breakdown.paypal_fee) {
                const currencyCode = payment.seller_receivable_breakdown.paypal_fee.currency_code;
                transactionFee = `${payment.seller_receivable_breakdown.paypal_fee.value} ${currencyCode}`;
            }

            this.payments.push({
                id: payment.id,
                type: this.$tc(`swag-paypal-payment.transactionHistory.states.${type}`),
                total: `${payment.amount.value} ${payment.amount.currency_code}`,
                create: this.formatDate(payment.create_time),
                createRaw: payment.create_time,
                update: this.formatDate(payment.update_time),
                transactionFee: transactionFee,
                status: payment.status
            });

            this.payments.sort((a, b) => {
                const dateA = new Date(a.createRaw);
                const dateB = new Date(b.createRaw);

                return dateA - dateB;
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
