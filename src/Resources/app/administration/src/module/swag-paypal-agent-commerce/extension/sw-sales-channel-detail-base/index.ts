import template from './sw-sales-channel-detail-base.html.twig';
import { PAYPAL_AGENT_COMMERCE_SALES_CHANNEL_TYPE_ID } from "SwagPayPal/constant/swag-paypal.constant";
import './sw-sales-channel-detail-base.scss';
import type * as PayPal from "SwagPayPal/types";

export default Shopware.Component.wrapComponentConfig({
    template,

    inject: [
        'systemConfigApiService',
        'SwagPayPalHoneyWebhookService',
    ],

    data(): {
        webhookRegistered: boolean;
        isRefreshingWebhook: boolean;
    } {
        return {
            webhookRegistered: false,
            isRefreshingWebhook: false,
        };
    },

    computed: {
        isAgentCommerceType(): boolean {
            // @ts-expect-error - salesChannel is defined in the parent component
            // eslint-disable-next-line @typescript-eslint/no-unsafe-member-access
            return this.salesChannel?.typeId === PAYPAL_AGENT_COMMERCE_SALES_CHANNEL_TYPE_ID;
        },

        isProductComparison(): boolean {
            return this.isAgentCommerceType || this.$super('isProductComparison') as boolean;
        },

        webhookStatusLabel() {
            return this.$t(`swag-paypal-settings.webhook.status.${this.webhookRegistered ? 'valid' : 'missing'}`);
        },

        webhookStatusVariant(): 'danger' | 'success' {
            return this.webhookRegistered ? 'success' : 'danger';
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            // @ts-expect-error - salesChannel is defined in the parent component
            // eslint-disable-next-line @typescript-eslint/no-unsafe-member-access
            const salesChannelId = this.salesChannel.id as string;

            this.systemConfigApiService
                .getValues('SwagPayPal.settings', salesChannelId)
                .then((values: PayPal.SystemConfig) => {
                    this.webhookRegistered = !!(values['SwagPayPal.settings.agentCommerceOnboarded'] ?? false);
                });
        },

        onRefreshWebhook() {
            this.isRefreshingWebhook = true;

            // @ts-expect-error - salesChannel is defined in the parent component
            // eslint-disable-next-line @typescript-eslint/no-unsafe-member-access
            this.SwagPayPalHoneyWebhookService.register(this.salesChannel.id as string).then(() => {
                this.createdComponent();

                this.isRefreshingWebhook = false;
            });
        },
    },
});
