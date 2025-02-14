import type * as PayPal from 'SwagPayPal/types';
import template from './swag-paypal-settings-webhook.html.twig';
import './swag-paypal-settings-webhook.scss';

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

    data(): {
        allWebhookStatus: Record<string, string | undefined>;
        status: 'none' | 'fetching' | 'refreshing';
    } {
        return {
            allWebhookStatus: {},
            status: 'none',
        };
    },

    computed: {
        settingsStore() {
            return Shopware.Store.get('swagPayPalSettings');
        },

        webhookStatus(): string | undefined {
            return this.allWebhookStatus[String(this.settingsStore.salesChannel)];
        },

        webhookStatusLabel() {
            return this.$t(`swag-paypal-settings.webhook.status.${this.webhookStatus || 'unknown'}`);
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

    watch: {
        'settingsStore.salesChannel': {
            immediate: true,
            handler() {
                this.fetchWebhookStatus(this.settingsStore.salesChannel);
            },
        },
    },

    methods: {
        fetchWebhookStatus(salesChannelId: string | null) {
            if (this.webhookStatus) {
                return;
            }

            this.status = 'fetching';

            this.SwagPayPalWebhookService.status(salesChannelId)
                .then((response) => {
                    this.allWebhookStatus[String(salesChannelId)] = response.result;
                    this.status = 'none';
                })
                .catch((errorResponse: PayPal.ServiceError) => {
                    this.createNotificationError({
                        title: this.$t('swag-paypal.notifications.webhook.title'),
                        message: this.$t('swag-paypal.notifications.webhook.errorMessage', {
                            message: this.createMessageFromError(errorResponse),
                        }),
                    });
                });
        },

        async onRefreshWebhook() {
            this.status = 'refreshing';

            await this.SwagPayPalWebhookService
                .register(this.settingsStore.salesChannel)
                .catch((errorResponse: PayPal.ServiceError) => {
                    this.createNotificationError({
                        title: this.$t('swag-paypal.notifications.webhook.title'),
                        message: this.$t('swag-paypal.notifications.webhook.errorMessage', {
                            message: this.createMessageFromError(errorResponse),
                        }),
                    });
                });

            this.status = 'none';

            this.fetchWebhookStatus(this.settingsStore.salesChannel);
        },
    },
});
