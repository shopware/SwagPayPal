import template from './swag-paypal-payment-action-refund.html.twig';
import { CAPTURE_RESOURCE_TYPE, REFUNDED_STATE, SALE_RESOURCE_TYPE } from '../../swag-paypal-payment-consts';

const { Component, Filter } = Shopware;
const utils = Shopware.Utils;

Component.register('swag-paypal-payment-action-refund', {
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
            captures: [],
            selectedCapture: {},
            existingRefunds: [],
            refundAmount: 0,
            refundableAmount: 0,
            refundDescription: '',
            refundReason: '',
            refundInvoiceNumber: '',
            isLoading: true,
            selectedCaptureId: '',
        };
    },

    computed: {
        dateFilter() {
            return Filter.getByName('date');
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.getRefundableCaptures();
            this.mapRefunds();
            this.selectedCaptureId = this.captures[0].id;
            this.isLoading = false;
            this.preserveCapture();
            this.refundAmount = Number(this.captures[0].refundableAmount);
        },

        getRefundableCaptures() {
            const relatedResources = this.paymentResource.transactions[0].related_resources;

            this.captures = relatedResources.reduce((accumulator, relatedResource) => {
                if (relatedResource.sale) {
                    const sale = relatedResource.sale;

                    if (sale.state !== REFUNDED_STATE) {
                        accumulator.push(this.formatCapture(sale, SALE_RESOURCE_TYPE));
                    }
                }

                if (relatedResource.capture) {
                    const capture = relatedResource.capture;

                    if (capture.state !== REFUNDED_STATE) {
                        accumulator.push(this.formatCapture(capture, CAPTURE_RESOURCE_TYPE));
                    }
                }

                if (relatedResource.refund) {
                    this.existingRefunds.push(relatedResource.refund);
                }

                return accumulator;
            }, this.captures);
        },

        formatCapture(resource, resourceType) {
            const createDate = this.dateFilter(resource.create_time);

            return {
                label: `${createDate} (${resource.amount.total} ${resource.amount.currency})` +
                    ` - ${resource.id} [${resource.state}]`,
                id: resource.id,
                refundableAmount: resource.amount.total,
                currency: resource.amount.currency,
                type: resourceType,
            };
        },

        mapRefunds() {
            this.existingRefunds.forEach((refund) => {
                const capture = this.captures.find((resource) => {
                    switch (resource.type) {
                        case CAPTURE_RESOURCE_TYPE:
                            return (refund.capture_id === resource.id);
                        case SALE_RESOURCE_TYPE:
                            return (refund.sale_id === resource.id);
                        default:
                            return false;
                    }
                });

                if (capture) {
                    let refundAmount = Number(refund.amount.total);
                    if (refundAmount < 0) {
                        refundAmount *= -1.0;
                    }
                    capture.refundableAmount -= refundAmount;
                }
            });
        },

        preserveCapture() {
            const capture = this.captures.find((selectedCapture) => {
                return selectedCapture.id === this.selectedCaptureId;
            });

            this.selectedCapture = capture;
            this.refundableAmount = Number(capture.refundableAmount);
            this.refundAmount = Number(capture.refundableAmount);
        },

        refund() {
            this.isLoading = true;

            let refundAmount = this.refundAmount;
            const description = this.refundDescription;
            const currency = this.selectedCapture.currency;
            const resourceType = this.selectedCapture.type;
            const resourceId = this.selectedCapture.id;
            const reason = this.refundReason;
            const invoiceNumber = this.refundInvoiceNumber;

            if (refundAmount === 0) {
                refundAmount = this.refundableAmount;
            }

            this.SwagPayPalPaymentService.refundPayment(
                this.orderId,
                resourceType,
                resourceId,
                refundAmount,
                currency,
                description,
                reason,
                invoiceNumber,
            ).then(() => {
                this.createNotificationSuccess({
                    message: this.$tc('swag-paypal-payment.refundAction.successMessage'),
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
    },
});
