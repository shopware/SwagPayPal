import template from './swag-paypal-payment-action-v2-refund.html.twig';
import { ORDER_CAPTURE_REFUNDED } from '../../../swag-paypal-payment-details-v2/swag-paypal-order-consts';

const { Component, Filter } = Shopware;

Component.register('swag-paypal-payment-action-v2-refund', {
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

        refundableAmount: {
            type: Number,
            required: false,
            default: 0,
        },
    },

    data() {
        return {
            captures: [],
            selectedCapture: {},
            refundAmount: 0,
            refundInvoiceNumber: '',
            refundNoteToPayer: '',
            selectedCaptureId: '',
            isLoading: true,
        };
    },

    computed: {
        dateFilter() {
            return Filter.getByName('date');
        },

        refundableAmountForSelectedCapture() {
            if (this.selectedCapture.amount.value > this.refundableAmount) {
                return Number(this.refundableAmount);
            }

            return Number(this.selectedCapture.amount.value);
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.getRefundableCaptures();
            const firstCapture = this.captures[0];
            this.selectedCaptureId = firstCapture.id;
            this.selectedCapture = firstCapture;
            this.refundAmount = this.refundableAmountForSelectedCapture;
            this.isLoading = false;
        },

        getRefundableCaptures() {
            const rawCaptures = this.paypalOrder.purchase_units[0].payments.captures;
            const refundableCaptures = [];

            rawCaptures.forEach((capture) => {
                if (capture.status !== ORDER_CAPTURE_REFUNDED) {
                    refundableCaptures.push(capture);
                }
            });

            this.captures = refundableCaptures;
        },

        setCapture() {
            this.selectedCapture = this.captures.find((selectedCapture) => {
                return selectedCapture.id === this.selectedCaptureId;
            });

            this.refundAmount = this.refundableAmountForSelectedCapture;
        },

        refund() {
            this.isLoading = true;

            let refundAmount = this.refundAmount;
            if (refundAmount === 0) {
                refundAmount = this.selectedCapture.amount.value;
            }

            this.SwagPayPalOrderService.refundCapture(
                this.orderTransactionId,
                this.selectedCapture.id,
                this.paypalOrder.id,
                this.selectedCapture.amount.currency_code,
                refundAmount,
                this.refundInvoiceNumber,
                this.refundNoteToPayer,
                this.paypalPartnerAttributionId,
            ).then(() => {
                this.createNotificationSuccess({
                    message: this.$tc('swag-paypal-payment.refundAction.successMessage'),
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
