import template from './sw-order.html.twig';

const { Component, State } = Shopware;
const Criteria = Shopware.Data.Criteria;

const paypalFormattedHandlerIdentifier = 'handler_swag_paypalpaymenthandler';
const paypalPuiFormattedHandlerIdentifier = 'handler_swag_paypalpuipaymenthandler';

Component.override('sw-order-detail', {
    template,

    data() {
        return {
            isPayPalPayment: false
        };
    },

    computed: {
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
                    this.isPayPalPayment = paymentMethod.formattedHandlerIdentifier === paypalFormattedHandlerIdentifier ||
                        paymentMethod.formattedHandlerIdentifier === paypalPuiFormattedHandlerIdentifier;
                }
            );
        }
    }
});
