import template from './swag-paypal-payment-action-capture.html.twig';

const { Component } = Shopware;
const utils = Shopware.Utils;

Component.register('swag-paypal-payment-action-capture', {
    template,

    inject: ['SwagPayPalPaymentService'],

    mixins: [
        Shopware.Mixin.getByName('notification'),
    ],

    props: {
        paymentResource: {
            type: Object,
            required: true,
        },

        maxCaptureValue: {
            type: Number,
            required: true,
        },

        orderId: {
            type: String,
            required: true,
        },
    },

    data() {
        return {
            isFinalCapture: true,
            captureValue: this.maxCaptureValue,
            isLoading: true,
            currency: this.paymentResource.transactions[0].amount.currency,
        };
    },

    computed: {
        showHint() {
            return this.isFinalCapture && this.captureValue !== this.maxCaptureValue;
        },
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

            this.isLoading = true;
            this.SwagPayPalPaymentService.capturePayment(
                this.orderId,
                resourceType,
                resourceId,
                captureAmount,
                currency,
                isFinalCapture,
            ).then(() => {
                this.createNotificationSuccess({
                    message: this.$tc('swag-paypal-payment.captureAction.successMessage'),
                });
                this.isLoading = false;
                this.$emit('modal-close');
                this.$nextTick(() => {
                    this.$router.replace(`${this.$route.path}?hash=${utils.createId()}`);
                });
            }).catch((errorResponse) => {
                try {
                    this.createNotificationError({
                        message: `${errorResponse.response.data.errors[0].title}: ${
                            errorResponse.response.data.errors[0].detail}`,
                        autoClose: false,
                    });
                } catch (e) {
                    this.createNotificationError({
                        message: `${errorResponse.title}: ${errorResponse.message}`,
                        autoClose: false,
                    });
                } finally {
                    this.isLoading = false;
                }
            });
        },

        getResourceId(paymentResource) {
            let relatedResourceId = null;
            paymentResource.transactions[0].related_resources.forEach((relatedResource) => {
                if (relatedResource.authorization) {
                    relatedResourceId = relatedResource.authorization.id;
                }
                if (relatedResource.order) {
                    relatedResourceId = relatedResource.order.id;
                }
            });
            return relatedResourceId;
        },
    },
});
