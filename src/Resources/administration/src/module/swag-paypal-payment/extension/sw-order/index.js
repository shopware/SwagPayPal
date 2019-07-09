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
            // ToDo with NEXT-3911: Replace with $createTitle(this.identifier);
            title: `${this.identifier} | ${this.$tc('swag-paypal-payment.general.title')} | ${this.$tc('global.sw-admin-menu.textShopwareAdmin')}`
        };
    },

    computed: {
        identifier() {
            return this.order !== null ? this.order.orderNumber : '';
        },

        paymentMethodStore() {
            return State.getStore('payment_method');
        },

        isEditable() {
            return !this.isPayPalPayment || this.$route.name !== 'swag.paypal.payment.detail';
        },

        // TODO remove with PT-10455
        showTabs() {
            return true;
        }
    },

    created() {
        // ToDo with NEXT-3911: Remove this Quickfix
        this.$router.push({ name: 'sw.order.detail', params: { id: this.orderId } })
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
