import template from './sw-order-detail.html.twig';
import './sw-order-detail.scss';

const { Component, Context } = Shopware;
const { hasOwnProperty } = Shopware.Utils.object;
const Criteria = Shopware.Data.Criteria;

Component.override('sw-order-detail', {
    template,

    data() {
        return {
            isPayPalPayment: false,
        };
    },

    computed: {
        isEditable() {
            return !this.isPayPalPayment || this.$route.name !== 'swag.paypal.payment.detail';
        },

        // TODO PPI-74 - Remove
        showTabs() {
            return true;
        },
    },

    watch: {
        orderId: {
            deep: true,
            handler() {
                if (!this.orderId) {
                    return;
                }

                const orderRepository = this.repositoryFactory.create('order');
                const orderCriteria = new Criteria(1, 1);
                orderCriteria.addAssociation('transactions');
                orderCriteria
                    .getAssociation('transactions')
                    .addSorting(Criteria.sort('createdAt', 'DESC'))
                    .setLimit(1);

                orderRepository.get(this.orderId, Context.api, orderCriteria).then((order) => {
                    const transaction = order.transactions.last();
                    if (!transaction) {
                        return;
                    }

                    this.isPayPalPayment = hasOwnProperty(transaction, 'customFields') &&
                        hasOwnProperty(transaction.customFields, 'swag_paypal_order_id');
                });
            },
            immediate: true,
        },
    },

    methods: {
        /**
         * @deprecated tag:v6.0.0 - Will be removed without replacement
         */
        setIsPayPalPayment(paymentMethodId) {
            this.isPayPalPayment = !!paymentMethodId;
        },
    },
});
