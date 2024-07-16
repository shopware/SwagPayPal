import { ref } from 'vue';
import type SwagPayPalCheckout from 'src/module/swag-paypal/components/swag-paypal-checkout';
import template from './swag-paypal-overview-card.html.twig';

type ConfigComponent = {
    save:() => Promise<{ payPalWebhookErrors?: string[] }>;
};

export default Shopware.Component.wrapComponentConfig({
    template,

    props: {
        paymentMethods: {
            type: Array as PropType<Array<TEntity<'payment_method'>>>,
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

    setup() {
        const swagPayPalConfigComponent = ref<ConfigComponent | null>(null);
        const swagPayPalCheckoutComponent = ref<InstanceType<typeof SwagPayPalCheckout> | null>(null);
        return { swagPayPalConfigComponent, swagPayPalCheckoutComponent };
    },

    methods: {
        async save() {
            this.isLoading = true;

            try {
                const response = await this.swagPayPalConfigComponent?.save();

                if (response?.payPalWebhookErrors) {
                    const errorMessage = this.$tc('swag-paypal.settingForm.messageWebhookError');
                    response.payPalWebhookErrors.forEach((error) => {
                        // eslint-disable-next-line @typescript-eslint/no-unsafe-call
                        this.createNotificationError({
                            message: `${errorMessage}<br><br><ul><li>${error}</li></ul>`,
                        });
                    });
                }

                await this.swagPayPalCheckoutComponent?.getPaymentMethodsAndMerchantIntegrations();
            } finally {
                this.isLoading = false;
            }
        },

        onChangeLoading(state: boolean) {
            this.isLoading = state;
        },
    },
});
