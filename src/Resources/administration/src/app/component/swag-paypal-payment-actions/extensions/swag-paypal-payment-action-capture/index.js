import { Component, Mixin } from 'src/core/shopware';
import utils from 'src/core/service/util.service';
import template from './swag-paypal-payment-action-capture.html.twig';

Component.register('swag-paypal-payment-action-capture', {
    template,

    inject: ['SwagPayPalPaymentService'],

    mixins: [
        Mixin.getByName('notification')
    ],

    props: {
        paymentResource: {
            type: Object,
            required: true
        },

        maxCaptureValue: {
            type: Number,
            required: true
        },

        orderId: {
            type: String,
            required: true
        }
    },

    data() {
        return {
            isFinalCapture: true,
            captureValue: this.maxCaptureValue,
            isLoading: true,
            currency: this.paymentResource.transactions[0].amount.currency
        };
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.isLoading = false;
        },

        capture() {
            const captureAmount = this.captureValue;
            const currency = this.currency;
            const isFinalCapture = this.isFinalCapture;
            const resourceType = this.paymentResource.intent;
            const resourceId = this.getResourceId(this.paymentResource);
            const orderId = this.$route.params.id;

            this.isLoading = true;
            this.SwagPayPalPaymentService.capturePayment(
                this.orderId,
                resourceType,
                resourceId,
                captureAmount,
                currency,
                isFinalCapture
            ).then(() => {
                this.createNotificationSuccess({
                    title: this.$tc('swag-paypal-payment.captureAction.successTitle'),
                    message: this.$tc('swag-paypal-payment.captureAction.successMessage')
                });
                this.isLoading = false;
                this.$emit('modal-close');
                this.$nextTick(() => {
                    this.$router.replace(`${this.$route.path}?hash=${utils.createId()}`);
                });
            })
                .catch((errorResponse) => {
                    this.createNotificationError({
                        title: errorResponse.title,
                        message: errorResponse.message
                    });

                    this.isLoading = false;
                });
        },

        getResourceId(paymentResource) {
            const firstRelatedResource = paymentResource.transactions[0].related_resources[0];
            if (firstRelatedResource.authorization) {
                return firstRelatedResource.authorization.id;
            }

            return firstRelatedResource.order.id;
        }
    }
});
