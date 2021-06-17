import template from './swag-paypal-payment-details-v1.html.twig';

const { Component, Filter } = Shopware;

Component.register('swag-paypal-payment-details-v1', {
    template,

    props: {
        paymentResource: {
            type: Object,
            required: true,
        },

        orderId: {
            type: String,
            required: true,
        },
    },

    data() {
        return {
            relatedResources: [],
            createDateTime: '',
            updateDateTime: '',
            currency: '',
            amount: {},
            payerId: '',
        };
    },

    computed: {
        dateFilter() {
            return Filter.getByName('date');
        },

        relatedResourceColumns() {
            return [
                {
                    property: 'type',
                    label: this.$tc('swag-paypal-payment.transactionHistory.types.type'),
                    rawData: true,
                },
                {
                    property: 'total',
                    label: this.$tc('swag-paypal-payment.transactionHistory.types.amount'),
                    rawData: true,
                },
                {
                    property: 'id',
                    label: this.$tc('swag-paypal-payment.transactionHistory.types.trackingId'),
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
                    property: 'paymentMode',
                    label: this.$tc('swag-paypal-payment.transactionHistory.types.paymentMode'),
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
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.createDateTime = this.formatDate(this.paymentResource.create_time);
            this.updateDateTime = this.formatDate(this.paymentResource.update_time);
            this.amount = this.paymentResource.transactions[0].amount;
            this.currency = this.paymentResource.transactions[0].amount.currency;
            if (this.paymentResource.payer && this.paymentResource.payer.payer_info) {
                this.payerId = this.paymentResource.payer.payer_info.payer_id;
            }

            this.setRelatedResources();
        },

        setRelatedResources() {
            const rawRelatedResources = this.paymentResource.transactions[0].related_resources;
            if (rawRelatedResources === null) {
                return;
            }

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
                createRaw: relatedResource.create_time,
                update: this.formatDate(relatedResource.update_time),
                transactionFee: transactionFee,
                status: relatedResource.state,
                paymentMode: relatedResource.payment_mode,
            });

            this.relatedResources.sort((a, b) => {
                const dateA = new Date(a.createRaw);
                const dateB = new Date(b.createRaw);

                return dateA - dateB;
            });
        },

        formatDate(dateTime) {
            return this.dateFilter(dateTime, {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
            });
        },
    },
});
