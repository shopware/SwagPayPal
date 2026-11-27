import type * as PayPal from 'SwagPayPal/types';
import type { WebhookUrlSource } from 'SwagPayPal/core/service/api/swag-paypal-webhook.service';
import template from './swag-paypal-settings-webhook.html.twig';
import './swag-paypal-settings-webhook.scss';

const STATUS_WEBHOOK_MISSING = 'missing';
const STATUS_WEBHOOK_INVALID = 'invalid';
const STATUS_WEBHOOK_VALID = 'valid';
const STATUS_WEBHOOK_DISABLED = 'disabled';

type UrlSources = PayPal.Api.Operations<'getWebhookStatus'>['urlSources'];

export default Shopware.Component.wrapComponentConfig({
    template,

    inject: [
        'acl',
        'SwagPayPalWebhookService',
        'settingsStoreSavingSettings',
    ],

    mixins: [
        Shopware.Mixin.getByName('swag-paypal-notification'),
    ],

    data(): {
        allWebhookStatus: Record<string, string | undefined>;
        allUrlSources: Record<string, UrlSources | undefined>;
        failedSalesChannels: Record<string, boolean>;
        urlSource: WebhookUrlSource;
        status: 'none' | 'fetching' | 'refreshing';
    } {
        return {
            allWebhookStatus: {},
            allUrlSources: {},
            failedSalesChannels: {},
            urlSource: 'administration',
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

        urlSources(): UrlSources {
            return this.allUrlSources[String(this.settingsStore.salesChannel)] ?? [];
        },

        // Both sources are only worth a choice when they differ
        hasUrlSourceChoice(): boolean {
            return this.urlSources.length > 1;
        },

        urlSourceOptions(): { value: WebhookUrlSource; label: string }[] {
            return this.urlSources.map(({ source, host }) => ({
                value: source,
                label: this.$t(`swag-paypal-settings.webhook.urlSource.${source}`, { host }),
            }));
        },

        webhookStatusLabel() {
            if (this.webhookStatus) {
                return this.$t(`swag-paypal-settings.webhook.status.${this.webhookStatus}`);
            }

            // unknown while fetching, unchecked once fetching ended without a status
            return this.$t(`swag-paypal-settings.webhook.status.${this.status === 'fetching' ? 'loading' : 'unknown'}`);
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

        canRefresh(): boolean {
            return this.status === 'none' && this.allowRefresh && this.acl.can('swag_paypal.editor');
        },

        refreshTooltip(): { message: string; showOnDisabledElements?: boolean; disabled?: boolean } {
            if (this.webhookStatus === STATUS_WEBHOOK_DISABLED) {
                return {
                    message: this.$t('swag-paypal-settings.webhook.tooltipDisabled'),
                    showOnDisabledElements: true,
                    disabled: false,
                };
            }

            return {
                message: '',
                disabled: true,
            };
        },
    },

    watch: {
        'settingsStore.salesChannel': {
            immediate: true,
            handler() {
                this.fetchWebhookStatus(this.settingsStore.salesChannel);
            },
        },
        settingsStoreSavingSettings: {
            handler(savingSettings) {
                if (savingSettings === 'success') {
                    this.forgetWebhookStatus(this.settingsStore.salesChannel);
                    this.fetchWebhookStatus(this.settingsStore.salesChannel);
                }
            },
        },
    },

    methods: {
        fetchWebhookStatus(salesChannelId: string | null) {
            // remembered, so selecting the Sales Channel again doesn't fetch and notify again
            if (this.webhookStatus || this.failedSalesChannels[String(salesChannelId)]) {
                return;
            }

            this.status = 'fetching';

            this.SwagPayPalWebhookService.status(salesChannelId)
                .then((response) => {
                    this.allWebhookStatus[String(salesChannelId)] = response.result;
                    this.allUrlSources[String(salesChannelId)] = response.urlSources;
                    this.status = 'none';
                })
                .catch((errorResponse: PayPal.ServiceError) => {
                    this.failedSalesChannels[String(salesChannelId)] = true;
                    this.status = 'none';

                    this.createNotificationError({
                        title: this.$t('swag-paypal.notifications.webhook.title'),
                        message: this.$t('swag-paypal.notifications.webhook.errorMessage', {
                            message: this.createMessageFromError(errorResponse),
                        }),
                    });
                });
        },

        forgetWebhookStatus(salesChannelId: string | null) {
            delete this.allWebhookStatus[String(salesChannelId)];
            delete this.allUrlSources[String(salesChannelId)];
            delete this.failedSalesChannels[String(salesChannelId)];
        },

        async onRefreshWebhook() {
            this.status = 'refreshing';

            await this.SwagPayPalWebhookService
                .register(this.settingsStore.salesChannel, this.hasUrlSourceChoice ? this.urlSource : undefined)
                .catch((errorResponse: PayPal.ServiceError) => {
                    this.createNotificationError({
                        title: this.$t('swag-paypal.notifications.webhook.title'),
                        message: this.$t('swag-paypal.notifications.webhook.errorMessage', {
                            message: this.createMessageFromError(errorResponse),
                        }),
                    });
                });

            this.status = 'none';

            this.forgetWebhookStatus(this.settingsStore.salesChannel);
            this.fetchWebhookStatus(this.settingsStore.salesChannel);
        },
    },
});
