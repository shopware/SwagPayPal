import template from './swag-paypal-payment-action-v2-void.html.twig';

const { Component } = Shopware;
const utils = Shopware.Utils;

Component.register('swag-paypal-payment-action-v2-void', {
    template,

    inject: ['SwagPayPalOrderService'],

    mixins: [
        'notification',
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
    },

    data() {
        return {
            isLoading: false,
        };
    },

    methods: {
        voidPayment() {
            this.isLoading = true;
            const authorization = this.paypalOrder.purchase_units[0].payments.authorizations[0];

            this.SwagPayPalOrderService.voidAuthorization(
                this.orderTransactionId,
                authorization.id,
                this.paypalPartnerAttributionId,
            ).then(() => {
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

        closeModal() {
            this.$emit('modal-close');
        },
    },
});
