import template from './swag-paypal-overview-card.html.twig';

const { Component } = Shopware;

Component.register('swag-paypal-overview-card', {
    template,

    props: {
        paymentMethods: {
            type: Array,
            required: true,
        },
    },

    data() {
        return {
            isLoading: false,
            salesChannels: [],
            config: null,
        };
    },

    computed: {
        pluginId() {
            if (this.paymentMethods.length === 0) {
                return '';
            }

            return this.paymentMethods[0].pluginId;
        },
    },

    methods: {
        async save() {
            this.isLoading = true;

            try {
                const response = await this.$refs.swagPayPalConfigComponent.save();

                if (response.payPalWebhookErrors) {
                    const errorMessage = this.$tc('swag-paypal.settingForm.messageWebhookError');
                    response.payPalWebhookErrors.forEach((error) => {
                        this.createNotificationError({
                            message: `${errorMessage}<br><br><ul><li>${error}</li></ul>`,
                        });
                    });
                }

                await this.$refs.swagPayPalCheckoutComponent.getPaymentMethodsAndMerchantIntegrations();
            } finally {
                this.isLoading = false;
            }
        },

        onChangeLoading(state) {
            this.isLoading = state;
        },
    },
});
