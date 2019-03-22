import { Mixin } from 'src/core/shopware';
import utils from 'src/core/service/util.service';
import template from './swag-paypal-payment-action-void.html.twig';

export default {
    name: 'swag-paypal-payment-action-void',

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

    data() {
        return {
            isLoading: false
        };
    },

    methods: {
        voidPayment() {
            this.isLoading = true;
            const resourceType = this.paymentResource.intent;
            const resourceId = this.getResourceId();

            this.SwagPayPalPaymentService.voidPayment(resourceType, resourceId).then(() => {
                this.createNotificationSuccess({
                    title: this.$tc('swag-paypal.voidAction.successTitle'),
                    message: this.$tc('swag-paypal.voidAction.successMessage')
                });
                this.isLoading = false;
                this.closeModal();
                this.$nextTick(() => {
                    this.$router.replace(`${this.$route.path}?hash=${utils.createId()}`);
                });
            }).catch((errorResponse) => {
                this.createNotificationError({
                    title: errorResponse.title,
                    message: errorResponse.message
                });
                this.isLoading = false;
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
            this.$emit('closeModal');
        }
    }
};
