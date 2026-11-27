import type * as PayPal from 'SwagPayPal/types';
import {
    SEVERITY_STATUSES,
    type DiagnosticsStatus,
} from 'SwagPayPal/constant/swag-paypal-diagnostics.constant';
import template from './swag-paypal-settings-diagnostics-reachability.html.twig';
import './swag-paypal-settings-diagnostics-reachability.scss';

type ReachabilityResult = PayPal.Diagnostics<'reachability'>;

type ReachabilityTest =
    | { status: 'testing' }
    | { status: 'failed' }
    | { status: 'done'; results: ReachabilityResult[] };

/**
 * Sends the reachability test of the `subject` for the Sales Channel itself.
 * `busy-change` tells whether the test is running.
 *
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

    emits: ['busy-change'],

    props: {
        salesChannelId: {
            type: String,
            required: true,
        },

        subject: {
            type: String as PropType<'callback' | 'webhook'>,
            required: true,
        },
    },

    data(): {
        tests: Record<string, ReachabilityTest>;
    } {
        return {
            tests: {},
        };
    },

    computed: {
        test(): ReachabilityTest | null {
            return this.tests[this.salesChannelId] ?? null;
        },

        isTesting(): boolean {
            return this.test?.status === 'testing';
        },

        results(): ReachabilityResult[] {
            return this.test?.status === 'done' ? this.test.results : [];
        },

        summaryStatus(): DiagnosticsStatus {
            if (this.results.some(({ severity }) => severity === 'error')) {
                return 'error';
            }

            return this.results.some(({ severity }) => severity === 'warning') ? 'warning' : 'passed';
        },

        // counted per kind, in the order of the results
        summaryCounts(): string {
            const counts = new Map<string, number>();
            this.results.forEach((result) => {
                const key = this.key(result);
                counts.set(key, (counts.get(key) ?? 0) + 1);
            });

            return [...counts].map(([key, count]) => {
                return this.$t(`swag-paypal-settings.diagnostics.reachability.counts.${key}`, count);
            }).join(' · ');
        },
    },

    watch: {
        salesChannelId: {
            immediate: true,
            handler(salesChannelId: string) {
                if (!this.tests[salesChannelId]) {
                    this.runTest(salesChannelId);
                }
            },
        },

        isTesting: {
            immediate: true,
            handler(isTesting: boolean) {
                this.$emit('busy-change', isTesting);
            },
        },
    },

    beforeUnmount() {
        this.$emit('busy-change', false);
    },

    methods: {
        async runTest(salesChannelId: string) {
            this.tests[salesChannelId] = { status: 'testing' };

            try {
                const results = this.subject === 'callback'
                    ? await this.SwagPayPalDiagnosticsService.expressCallbackReachability(salesChannelId)
                    : await this.SwagPayPalDiagnosticsService.webhookReachability(salesChannelId);

                this.tests[salesChannelId] = { status: 'done', results };
            } catch (errorResponse) {
                this.tests[salesChannelId] = { status: 'failed' };

                this.createNotificationError({
                    title: this.$t('swag-paypal-settings.diagnostics.reachability.title'),
                    message: this.$t('swag-paypal-settings.diagnostics.reachability.errorMessage', {
                        message: this.createMessageFromError(errorResponse as PayPal.ServiceError),
                    }),
                });
            }
        },

        key(result: ReachabilityResult): string {
            return result.error ? `errors.${result.error}` : 'reachable';
        },

        status(result: ReachabilityResult): DiagnosticsStatus {
            return result.severity ? SEVERITY_STATUSES[result.severity] : 'passed';
        },
    },
});
