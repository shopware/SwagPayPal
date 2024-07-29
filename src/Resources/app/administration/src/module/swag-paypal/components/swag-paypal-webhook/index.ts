import type * as PayPal from 'src/types';
import template from './swag-paypal-webhook.html.twig';
import './swag-paypal-webhook.scss';

const STATUS_WEBHOOK_MISSING = 'missing';
const STATUS_WEBHOOK_INVALID = 'invalid';
const STATUS_WEBHOOK_VALID = 'valid';

export default Shopware.Component.wrapComponentConfig({
    template,

    inject: [
        'acl',
        'SwagPayPalWebhookService',
    ],

    mixins: [
        Shopware.Mixin.getByName('swag-paypal-notification'),
    ],

    props: {
        isLoading: {
            type: Boolean,
            required: false,
            default: false,
        },

        selectedSalesChannelId: {
            type: String,
            required: false,
            default: null,
        },
    },

    data(): {
        webhookStatus: string | null;
        isFetchingStatus: boolean;
        isRefreshing: boolean;
    } {
        return {
            webhookStatus: null,
            isFetchingStatus: false,
            isRefreshing: false,
        };
    },

    computed: {
        webhookStatusLabel() {
            return this.$tc(`swag-paypal.webhook.status.${this.webhookStatus || 'unknown'}`);
        },

        webhookStatusVariant(): 'danger' | 'warning' | 'success' | 'neutral' {
            switch (this.webhookStatus) {
                case STATUS_WEBHOOK_MISSING:
                    return 'danger';

                case STATUS_WEBHOOK_INVALID:
                    return 'warning';

                case STATUS_WEBHOOK_VALID:
                    return 'success';

                default:
                    return 'neutral';
            }
        },

        allowRefresh(): boolean {
            return [STATUS_WEBHOOK_INVALID, STATUS_WEBHOOK_MISSING]
                .includes(this.webhookStatus ?? '');
        },
    },

    created() {
        this.fetchWebhookStatus();
    },

    methods: {
        async fetchWebhookStatus() {
            this.isFetchingStatus = true;

            const response = await this.SwagPayPalWebhookService.status(this.selectedSalesChannelId);

            this.webhookStatus = response.result ?? null;

            this.isFetchingStatus = false;
        },

        async onRefreshWebhook() {
            this.isRefreshing = true;

            await this.SwagPayPalWebhookService
                .register(this.selectedSalesChannelId)
                .catch((errorResponse: PayPal.ServiceError) => {
                    this.createNotificationFromError({ errorResponse, title: 'swag-paypal.webhook.refreshFailed.title' });
                });

            this.isRefreshing = false;
            return this.fetchWebhookStatus();
        },
    },
});
