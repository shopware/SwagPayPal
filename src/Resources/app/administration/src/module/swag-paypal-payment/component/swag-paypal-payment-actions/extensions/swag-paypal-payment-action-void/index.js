import template from './swag-paypal-payment-action-void.html.twig';

const { Component } = Shopware;
const utils = Shopware.Utils;

Component.register('swag-paypal-payment-action-void', {
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

        orderId: {
            type: String,
            required: true,
        },
    },

    data() {
        return {
            isLoading: false,
        };
    },

    methods: {
        voidPayment() {
            this.isLoading = true;
            const resourceType = this.paymentResource.intent;
            const resourceId = this.getResourceId();
            const orderId = this.$route.params.id;

            this.SwagPayPalPaymentService.voidPayment(this.orderId, resourceType, resourceId, orderId).then(() => {
                this.createNotificationSuccess({
                    message: this.$tc('swag-paypal-payment.voidAction.successMessage'),
                });
                this.isLoading = false;
                this.closeModal();
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

        getResourceId() {
            const firstRelatedResource = this.paymentResource.transactions[0].related_resources[0];

            if (firstRelatedResource.order) {
                return firstRelatedResource.order.id;
            }

            return firstRelatedResource.authorization.id;
        },

        closeModal() {
            this.$emit('modal-close');
        },
    },
});
