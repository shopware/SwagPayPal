import { Component, State } from 'src/core/shopware';
import { get } from 'src/core/service/utils/object.utils';
import template from './sw-order.html.twig';

const paypalFormattedHandlerIdentifier = 'handler_swag_paypalpaymenthandler';

Component.override('sw-order-detail', {
    template,

    data() {
        return {
            isPayPalPayment: false
        };
    },

    metaInfo() {
        return {
            title: `${this.identifier} | ${this.$tc('swag-paypal-payment.general.title')}`
        };
    },

    computed: {
        identifier() {
            return this.order !== null ? this.order.orderNumber : '';
        },

        paymentMethodStore() {
            return State.getStore('payment_method');
        },

        // TODO remove with PT-10455
        showTabs() {
            return true;
        }
    },

    watch: {
        order: {
            deep: true,
            handler() {
                const paymentMethodId = get(this.order, 'transactions[0].paymentMethod.id');
                if (paymentMethodId !== undefined && paymentMethodId !== null) {
                    this.setIsPayPalPayment(paymentMethodId);
                }
            }
        }
    },

    methods: {
        setIsPayPalPayment(paymentMethodId) {
            if (!paymentMethodId) {
                return;
            }
            this.paymentMethodStore.getByIdAsync(paymentMethodId).then(
                (paymentMethod) => {
                    this.isPayPalPayment = paymentMethod.formattedHandlerIdentifier === paypalFormattedHandlerIdentifier;
                }
            );
        }
    }
});
