import template from './sw-sales-channel-detail-base.html.twig';
import { PAYPAL_AGENTIC_COMMERCE_SALES_CHANNEL_TYPE_ID } from "SwagPayPal/constant/swag-paypal.constant";
import './sw-sales-channel-detail-base.scss';
import type * as PayPal from "SwagPayPal/types";

const { Criteria } = Shopware.Data;

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
        isAgenticCommerceType(): boolean {
            // @ts-expect-error - salesChannel is defined in the parent component
            // eslint-disable-next-line @typescript-eslint/no-unsafe-member-access
            return this.salesChannel?.typeId === PAYPAL_AGENTIC_COMMERCE_SALES_CHANNEL_TYPE_ID;
        },

        isProductComparison(): boolean {
            return this.isAgenticCommerceType || this.$super('isProductComparison') as boolean;
        },

        // For now, we support only US sales channels
        agenticStorefrontSalesChannelCriteria(): TCriteria {
            const storefrontSalesChannelCriteria = this.storefrontSalesChannelCriteria as TCriteria;
            const criteria = Criteria.fromCriteria(storefrontSalesChannelCriteria);

            criteria.addFilter(Criteria.equals('country.iso3', 'USA'));

            return criteria;
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
                    this.webhookRegistered = !!(values['SwagPayPal.settings.agenticCommerceOnboarded'] ?? false);
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
