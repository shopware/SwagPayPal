import template from './swag-paypal-payment-action-v2-capture.html.twig';

const { Component } = Shopware;

Component.register('swag-paypal-payment-action-v2-capture', {
    template,

    inject: ['SwagPayPalOrderService'],

    mixins: [
        Shopware.Mixin.getByName('notification'),
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

        captureableAmount: {
            type: Number,
            required: true,
        },
    },

    data() {
        return {
            authorization: {},
            isFinalCapture: true,
            captureAmount: this.captureableAmount,
            captureInvoiceNumber: '',
            captureNoteToPayer: '',
            currencyCode: '',
            isLoading: true,
        };
    },

    computed: {
        showHint() {
            return this.isFinalCapture && this.captureAmount !== this.captureableAmount;
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.authorization = this.paypalOrder.purchase_units[0].payments.authorizations[0];
            this.currencyCode = this.authorization.amount.currency_code;
            this.isLoading = false;
        },

        capture() {
            this.isLoading = true;

            let captureAmount = this.captureAmount;
            if (captureAmount === 0) {
                captureAmount = this.captureableAmount;
            }

            this.SwagPayPalOrderService.captureAuthorization(
                this.orderTransactionId,
                this.authorization.id,
                this.currencyCode,
                captureAmount,
                this.captureInvoiceNumber,
                this.captureNoteToPayer,
                this.paypalPartnerAttributionId,
                this.isFinalCapture,
            ).then(() => {
                this.createNotificationSuccess({
                    message: this.$tc('swag-paypal-payment.captureAction.successMessage'),
                });
                this.isLoading = false;
                this.$emit('modal-close');
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
    },
});
