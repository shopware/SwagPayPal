import type SwagPayPalCheckout from 'src/module/swag-paypal/components/swag-paypal-checkout';
import template from './swag-paypal-overview-card.html.twig';

type ConfigComponent = {
    save:() => Promise<{ payPalWebhookErrors?: string[] }>;
};

export default Shopware.Component.wrapComponentConfig({
    template,

    mixins: [
        Shopware.Mixin.getByName('notification'),
    ],

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
        swagPayPalConfigComponent(): ConfigComponent | null {
            return this.$refs.swagPayPalConfigComponent || null;
        },

        swagPayPalCheckoutComponent(): InstanceType<typeof SwagPayPalCheckout> | null {
            return this.$refs.swagPayPalCheckoutComponent || null;
        },

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
                const response = await (this.$refs.swagPayPalConfigComponent as ConfigComponent|null)?.save();

                if (response?.payPalWebhookErrors) {
                    const errorMessage = this.$tc('swag-paypal.settingForm.messageWebhookError');
                    response.payPalWebhookErrors.forEach((error) => {
                        this.createNotificationError({
                            message: `${errorMessage}<br><br><ul><li>${error}</li></ul>`,
                        });
                    });
                }

                await (this.$refs.swagPayPalCheckoutComponent as InstanceType<typeof SwagPayPalCheckout>|null)?.getPaymentMethodsAndMerchantIntegrations();
            } finally {
                this.isLoading = false;
            }
        },

        onChangeLoading(state: boolean) {
            this.isLoading = state;
        },
    },
});
