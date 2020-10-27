import template from './swag-paypal-payment-actions.html.twig';
import './swag-paypal-payment-actions.scss';
import './extensions/swag-paypal-payment-action-capture';
import './extensions/swag-paypal-payment-action-refund';
import './extensions/swag-paypal-payment-action-void';
import {
    VOIDED_STATE,
    CAPTURED_STATE,
    COMPLETED_STATE,
    CANCELLED_STATE,
    FAILED_STATE
} from './swag-paypal-payment-consts';

const { Component } = Shopware;

Component.register('swag-paypal-payment-actions', {
    template,

    inject: [
        'acl'
    ],

    props: {
        paymentResource: {
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
            modalType: '',
            refundableAmount: 0,
            captureableAmount: 0,
            showVoidButton: false
        };
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.setPaymentActionAmounts();
            this.setShowVoidButton();
            this.captureableAmount = this.formatAmount(this.captureableAmount);
            this.refundableAmount = this.formatAmount(this.refundableAmount);
        },

        spawnModal(modalType) {
            this.modalType = modalType;
        },

        closeModal() {
            this.modalType = '';
        },

        setPaymentActionAmounts() {
            const transactions = this.paymentResource.transactions;
            if (!transactions) {
                return;
            }

            const relatedResources = transactions[0].related_resources;

            relatedResources.forEach((relatedResource) => {
                if (relatedResource.authorization) {
                    if (relatedResource.authorization.state !== COMPLETED_STATE) {
                        this.captureableAmount += Number(relatedResource.authorization.amount.total);
                    }
                }

                if (relatedResource.order) {
                    if (relatedResource.order.state !== COMPLETED_STATE) {
                        this.captureableAmount += Number(relatedResource.order.amount.total);
                    }
                }

                if (relatedResource.sale) {
                    this.refundableAmount += Number(relatedResource.sale.amount.total);
                }

                if (relatedResource.capture) {
                    const captureAmount = Number(relatedResource.capture.amount.total);
                    this.captureableAmount -= captureAmount;
                    this.refundableAmount += captureAmount;
                }

                if (relatedResource.refund) {
                    if (relatedResource.refund.state !== FAILED_STATE
                        && relatedResource.refund.state !== CANCELLED_STATE
                    ) {
                        let refund = Number(relatedResource.refund.amount.total);
                        if (refund > 0) {
                            refund *= -1.0;
                        }
                        this.refundableAmount += refund;
                    }
                }
            });
        },

        setShowVoidButton() {
            const transactions = this.paymentResource.transactions;
            if (!transactions) {
                return;
            }

            const firstRelatedResource = transactions[0].related_resources[0];

            if (!firstRelatedResource) {
                return;
            }

            const nonVoidAbleStates = [VOIDED_STATE, CAPTURED_STATE, COMPLETED_STATE];

            if (firstRelatedResource.sale) {
                return;
            }

            if (firstRelatedResource.order) {
                const order = firstRelatedResource.order;
                if (nonVoidAbleStates.includes(order.state)) {
                    this.captureableAmount = 0;
                    return;
                }

                this.showVoidButton = true;
            }

            if (firstRelatedResource.authorization) {
                const authorization = firstRelatedResource.authorization;
                if (nonVoidAbleStates.includes(authorization.state)) {
                    this.captureableAmount = 0;
                    return;
                }

                this.showVoidButton = true;
            }
        },

        formatAmount(value) {
            return Number(`${Math.round(`${value}e2`)}e-2`);
        }
    }
});
