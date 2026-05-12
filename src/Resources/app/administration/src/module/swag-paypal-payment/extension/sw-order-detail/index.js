import template from './sw-order-detail.html.twig';

const { Component } = Shopware;
const { isEmpty } = Shopware.Utils.types;

Component.override('sw-order-detail', {
    template,

    computed: {
        isPayPalPayment() {
            const transaction = this.order?.transactions?.last();

            return this.order
                && !isEmpty(transaction)
                && (transaction?.customFields?.swag_paypal_order_id
                    || transaction?.customFields?.swag_paypal_transaction_id);
        },

        isEditable() {
            return !this.isPayPalPayment || this.$route.name !== 'swag.paypal.payment.detail';
        },
    },
});
