import type * as PayPal from 'SwagPayPal/types';
import {
    DATE_FORMAT,
    SEVERITY_STATUSES,
    type DiagnosticsStatus,
} from 'SwagPayPal/constant/swag-paypal-diagnostics.constant';
import type { WebhookTestTarget } from 'SwagPayPal/core/service/api/swag-paypal-diagnostics.service';
import template from './swag-paypal-settings-diagnostics-webhook.html.twig';
import './swag-paypal-settings-diagnostics-webhook.scss';

type WebhookResult = PayPal.Diagnostics<'webhook_result'>;
type WebhookFinding = PayPal.Diagnostics<'webhook_finding'>;

type Check =
    | { status: 'checking' }
    | { status: 'failed' }
    | { status: 'done'; result: WebhookResult };

type WebhookTest =
    | { status: 'sending'; target: WebhookTestTarget }
    | { status: 'waiting'; target: WebhookTestTarget }
    | { status: 'arrived'; target: WebhookTestTarget; seconds: number }
    | { status: 'missing'; target: WebhookTestTarget }
    | { status: 'failed'; target: WebhookTestTarget };

const TEST_POLL_INTERVAL = 3000;
const TEST_POLL_ATTEMPTS = 20;

/**
 * @private
 */
export default Shopware.Component.wrapComponentConfig({
    template,

    inject: [
        'SwagPayPalDiagnosticsService',
    ],

    mixins: [
        Shopware.Mixin.getByName('swag-paypal-notification'),
    ],

    data(): {
        checks: Record<string, Check>;
        webhookTests: Record<string, WebhookTest>;
        isTestingReachability: boolean;
        isUnmounted: boolean;
    } {
        return {
            checks: {},
            webhookTests: {},
            isTestingReachability: false,
            isUnmounted: false,
        };
    },

    computed: {
        settingsStore() {
            return Shopware.Store.get('swagPayPalSettings');
        },

        salesChannelId(): string | null {
            return this.settingsStore.salesChannel;
        },

        check(): Check | null {
            return this.salesChannelId ? this.checks[this.salesChannelId] ?? null : null;
        },

        result(): WebhookResult | null {
            return this.check?.status === 'done' ? this.check.result : null;
        },

        isChecking(): boolean {
            return this.check?.status === 'checking';
        },

        hasUrls(): boolean {
            return !!this.result && (!!this.result.registeredUrl || this.result.candidateUrls.length > 0);
        },

        lastWebhookDate(): string {
            return this.result?.lastWebhook ? this.formatDate(this.result.lastWebhook.at) : '';
        },

        webhookTest(): WebhookTest | null {
            return this.salesChannelId ? this.webhookTests[this.salesChannelId] ?? null : null;
        },

        isTestRunning(): boolean {
            return this.webhookTest?.status === 'sending' || this.webhookTest?.status === 'waiting';
        },

        isBusy(): boolean {
            return this.isChecking || this.isTestingReachability || this.isTestRunning;
        },
    },

    watch: {
        salesChannelId: {
            immediate: true,
            handler(salesChannelId: string | null) {
                if (salesChannelId && !this.checks[salesChannelId]) {
                    this.runCheck(salesChannelId);
                }
            },
        },
    },

    beforeUnmount() {
        this.isUnmounted = true;
    },

    methods: {
        async runCheck(salesChannelId: string) {
            this.checks[salesChannelId] = { status: 'checking' };

            let result: WebhookResult;
            try {
                result = await this.SwagPayPalDiagnosticsService.webhook(salesChannelId);
            } catch (errorResponse) {
                this.checks[salesChannelId] = { status: 'failed' };

                this.createNotificationError({
                    title: this.$t('swag-paypal-settings.diagnostics.webhook.title'),
                    message: this.$t('swag-paypal-settings.diagnostics.webhook.errorMessage', {
                        message: this.createMessageFromError(errorResponse as PayPal.ServiceError),
                    }),
                });

                return;
            }

            this.checks[salesChannelId] = { status: 'done', result };
        },

        onCheck() {
            if (this.salesChannelId) {
                this.runCheck(this.salesChannelId);
            }
        },

        async sendTestWebhook(salesChannelId: string, target: WebhookTestTarget) {
            this.webhookTests[salesChannelId] = { status: 'sending', target };

            try {
                await this.SwagPayPalDiagnosticsService.webhookTest(salesChannelId, target);
            } catch (errorResponse) {
                this.webhookTests[salesChannelId] = { status: 'failed', target };

                this.createNotificationError({
                    title: this.$t('swag-paypal-settings.diagnostics.webhook.title'),
                    message: this.$t('swag-paypal-settings.diagnostics.webhook.test.errorMessage', {
                        message: this.createMessageFromError(errorResponse as PayPal.ServiceError),
                    }),
                });

                return;
            }

            this.webhookTests[salesChannelId] = { status: 'waiting', target };
            const startedAt = Date.now();

            for (let attempt = 0; attempt < TEST_POLL_ATTEMPTS; attempt += 1) {
                await new Promise((resolve) => { setTimeout(resolve, TEST_POLL_INTERVAL); });

                if (this.isUnmounted) {
                    return;
                }

                let arrived = false;
                try {
                    ({ arrived } = await this.SwagPayPalDiagnosticsService.webhookTestArrival(salesChannelId));
                } catch {
                    // A failed poll is just no answer yet
                }

                if (this.isUnmounted) {
                    return;
                }

                if (arrived) {
                    this.webhookTests[salesChannelId] = {
                        status: 'arrived',
                        target,
                        seconds: Math.round((Date.now() - startedAt) / 1000),
                    };

                    await this.runCheck(salesChannelId);

                    return;
                }
            }

            this.webhookTests[salesChannelId] = { status: 'missing', target };
        },

        onTest(target: WebhookTestTarget) {
            if (this.salesChannelId) {
                this.sendTestWebhook(this.salesChannelId, target);
            }
        },

        testTargetLabel(target: WebhookTestTarget): string {
            const labels = {
                registered: 'labelRegisteredUrl',
                administration: 'labelAdministrationUrl',
                app_url: 'labelAppUrl',
            } as const;

            return this.$t(`swag-paypal-settings.diagnostics.webhook.${labels[target]}`);
        },

        findingParams(finding: WebhookFinding) {
            const missedEvents = this.result?.missedEvents;

            return {
                date: finding.type === 'missed_events' && missedEvents
                    ? this.formatDate(missedEvents.newestAt)
                    : this.lastWebhookDate,
                count: missedEvents ? `${missedEvents.count}${missedEvents.more ? '+' : ''}` : '',
                eventType: missedEvents?.newestEventType ?? '',
            };
        },

        findingSummary(finding: WebhookFinding): string {
            return this.$t(
                `swag-paypal-settings.diagnostics.webhook.findings.${finding.type}.summary`,
                this.findingParams(finding),
                finding.type === 'missed_events' ? this.result?.missedEvents?.count ?? 1 : 1,
            );
        },

        isFoldable(finding: WebhookFinding): boolean {
            return finding.type === 'registration_invalid' || finding.type === 'registration_outdated';
        },

        findingStatus(finding: WebhookFinding): DiagnosticsStatus {
            return SEVERITY_STATUSES[finding.severity];
        },

        formatDate(value: string): string {
            return Shopware.Filter.getByName('date')(value, DATE_FORMAT);
        },
    },
});
