import { Component } from 'src/core/shopware';
import template from './swag-paypal-payment-actions.html.twig';
import './swag-paypal-payment-actions.scss';
import './extensions/swag-paypal-payment-action-capture';
import './extensions/swag-paypal-payment-action-refund';
import './extensions/swag-paypal-payment-action-void';
import {
    REFUNDED_STATE,
    PARTIALLY_REFUNDED_STATE,
    VOIDED_STATE,
    CAPTURED_STATE,
    COMPLETED_STATE
} from './swag-paypal-payment-consts';

Component.register('swag-paypal-payment-actions', {
    template,

    props: {
        paymentResource: {
            type: Object,
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
            const relatedResources = this.paymentResource.transactions[0].related_resources;

            relatedResources.forEach((relatedResource) => {
                if (relatedResource.authorization) {
                    this.captureableAmount = relatedResource.authorization.amount.total;
                }

                if (relatedResource.order) {
                    this.captureableAmount = relatedResource.order.amount.total;
                }

                if (relatedResource.sale) {
                    if (!(relatedResource.sale.state === REFUNDED_STATE)) {
                        this.refundableAmount = relatedResource.sale.amount.total;
                    }
                }

                if (relatedResource.capture) {
                    const captureAmount = relatedResource.capture.amount.total;
                    this.captureableAmount -= captureAmount;

                    if (relatedResource.capture.state !== REFUNDED_STATE
                        && relatedResource.capture.state !== PARTIALLY_REFUNDED_STATE) {
                        this.refundableAmount += captureAmount;
                    }
                }
            });
        },

        setShowVoidButton() {
            const firstRelatedResource = this.paymentResource.transactions[0].related_resources[0];
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
