import { Component, State } from 'src/core/shopware';
import template from './sw-order.html.twig';
import Criteria from 'src/core/data-new/criteria.data';

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
        this.$router.push({ name: 'sw.order.detail', params: { id: this.orderId } });
    },

    watch: {
        orderId: {
            deep: true,
            handler() {
                if (!this.orderId) {
                    this.setIsPayPalPayment(null);
                    return;
                }

                const orderRepository = this.repositoryFactory.create('order');
                const orderCriteria = new Criteria(1, 1);
                orderCriteria.addAssociation('transactions');

                orderRepository.get(this.orderId, this.context, orderCriteria).then((order) => {
                    if (order.transactions.length <= 0 ||
                        !order.transactions[0].paymentMethodId
                    ) {
                        this.setIsPayPalPayment(null);
                        return;
                    }

                    const paymentMethodId = order.transactions[0].paymentMethodId;

                    if (paymentMethodId !== undefined && paymentMethodId !== null) {
                        this.setIsPayPalPayment(paymentMethodId);
                    }
                });
            },
            immediate: true
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
