import template from './swag-paypal-payment-actions-v2.html.twig';
import './extensions/swag-paypal-payment-action-v2-capture';
import './extensions/swag-paypal-payment-action-v2-refund';
import './extensions/swag-paypal-payment-action-v2-void';
import './swag-paypal-payment-actions-v2.scss';

const { Component } = Shopware;

Component.register('swag-paypal-payment-actions-v2', {
    template,

    inject: [
        'acl',
    ],

    props: {
        paypalOrder: {
            type: Object,
            required: true,
        },

        orderTransactionId: {
            type: String,
            required: true,
        },

        paypalPartnerAttributionId: {
            type: String,
            required: true,
        },

        refundableAmount: {
            type: Number,
            required: true,
        },

        captureableAmount: {
            type: Number,
            required: true,
        },

        showVoidButton: {
            type: Boolean,
            required: true,
        },
    },

    data() {
        return {
            modalType: '',
        };
    },

    methods: {
        spawnModal(modalType) {
            this.modalType = modalType;
        },

        closeModal() {
            this.modalType = '';
            this.$emit('reload-paypal-order');
        },
    },
});
