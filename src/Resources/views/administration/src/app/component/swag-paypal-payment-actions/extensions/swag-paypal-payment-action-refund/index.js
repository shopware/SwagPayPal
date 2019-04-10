import { Filter, Mixin } from 'src/core/shopware';
import utils from 'src/core/service/util.service';
import template from './swag-paypal-payment-action-refund.html.twig';
import {
    REFUNDED_STATE,
    PARTIALLY_REFUNDED_STATE,
    SALE_RESOURCE_TYPE,
    CAPTURE_RESOURCE_TYPE
} from '../../swag-paypal-payment-consts';

export default {
    name: 'swag-paypal-payment-action-refund',

    template,

    inject: ['SwagPayPalPaymentService'],

    mixins: [
        Mixin.getByName('notification')
    ],

    props: {
        paymentResource: {
            type: Object,
            required: true
        }
    },

    computed: {
        dateFilter() {
            return Filter.getByName('date');
        }
    },

    data() {
        return {
            captures: [],
            selectedCapture: {},
            refundAmount: 0,
            refundableAmount: 0,
            isLoading: true,
            selectedCaptureId: ''
        };
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.getRefundableCaptures();
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

                    if (capture.state !== REFUNDED_STATE && capture.state !== PARTIALLY_REFUNDED_STATE) {
                        accumulator.push(this.formatCapture(capture, CAPTURE_RESOURCE_TYPE));
                    }
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
                type: resourceType
            };
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

            const refundAmount = this.refundAmount;
            const currency = this.selectedCapture.currency;
            const resourceType = this.selectedCapture.type;
            const resourceId = this.selectedCapture.id;

            this.SwagPayPalPaymentService.refundPayment(
                resourceType,
                resourceId,
                refundAmount,
                currency
            ).then(() => {
                this.createNotificationSuccess({
                    title: this.$tc('swag-paypal-payment.refundAction.successTitle'),
                    message: this.$tc('swag-paypal-payment.refundAction.successMessage')
                });
                this.isLoading = false;
                this.$emit('closeModal');
                this.$nextTick(() => {
                    this.$router.replace(`${this.$route.path}?hash=${utils.createId()}`);
                });
            }).catch((errorResponse) => {
                this.createErrorNotification({
                    title: errorResponse.title,
                    message: errorResponse.message,
                    autoClose: false
                });
            });
        }
    }
};
