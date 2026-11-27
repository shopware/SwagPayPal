import type * as PayPal from 'SwagPayPal/types';
import {
    DATE_FORMAT,
    SEVERITY_STATUSES,
    type DiagnosticsStatus,
} from 'SwagPayPal/constant/swag-paypal-diagnostics.constant';
import template from './swag-paypal-settings-diagnostics-express-callback.html.twig';

type ExpressCallbackResult = PayPal.Diagnostics<'express_callback_result'>;
type ExpressCallbackFinding = PayPal.Diagnostics<'express_callback_finding'>;

type Check =
    | { status: 'checking' }
    | { status: 'failed' }
    | { status: 'done'; result: ExpressCallbackResult };

const STATUSES: Record<Exclude<ExpressCallbackResult['status'], 'active'>, DiagnosticsStatus> = {
    inactive: 'not-observed',
    local_environment: 'warning',
    disabled: 'info',
};

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
        isTestingReachability: boolean;
    } {
        return {
            checks: {},
            isTestingReachability: false,
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

        result(): ExpressCallbackResult | null {
            return this.check?.status === 'done' ? this.check.result : null;
        },

        isChecking(): boolean {
            return this.check?.status === 'checking';
        },

        status(): DiagnosticsStatus | null {
            if (!this.result || this.result.status === 'active') {
                return null;
            }

            return STATUSES[this.result.status];
        },

        lastCallbackDate(): string {
            return this.result?.lastCallback ? this.formatDate(this.result.lastCallback.at) : '';
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

    methods: {
        async runCheck(salesChannelId: string) {
            this.checks[salesChannelId] = { status: 'checking' };

            let result: ExpressCallbackResult;
            try {
                result = await this.SwagPayPalDiagnosticsService.expressCallback(salesChannelId);
            } catch (errorResponse) {
                this.checks[salesChannelId] = { status: 'failed' };

                this.createNotificationError({
                    title: this.$t('swag-paypal-settings.diagnostics.expressCallback.title'),
                    message: this.$t('swag-paypal-settings.diagnostics.expressCallback.errorMessage', {
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

        findingStatus(finding: ExpressCallbackFinding): DiagnosticsStatus {
            return SEVERITY_STATUSES[finding.severity];
        },

        formatDate(value: string): string {
            return Shopware.Filter.getByName('date')(value, DATE_FORMAT);
        },
    },
});
