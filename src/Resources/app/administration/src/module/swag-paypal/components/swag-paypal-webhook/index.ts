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
        Shopware.Mixin.getByName('notification'),
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

    data() {
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

        webhookStatusVariant() {
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

        allowRefresh() {
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
                .catch((response) => {
                    this.createNotificationError({
                        title: this.$tc('swag-paypal.webhook.refreshFailed.title'),
                        message: response.response?.data?.errors?.[0]?.detail
                            ?? this.$tc('swag-paypal.webhook.refreshFailed.errorUnknown'),
                    });
                });

            this.isRefreshing = false;
            return this.fetchWebhookStatus();
        },
    },
});
