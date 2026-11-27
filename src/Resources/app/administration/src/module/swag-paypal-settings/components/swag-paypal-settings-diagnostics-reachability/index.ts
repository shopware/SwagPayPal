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
 * Shows the results of the reachability test of the `subject`.
 *
 * @private
 */
export default Shopware.Component.wrapComponentConfig({
    template,

    props: {
        test: {
            type: Object as PropType<ReachabilityTest | null>,
            required: false,
            default: null,
        },

        subject: {
            type: String as PropType<'callback' | 'webhook'>,
            required: true,
        },
    },

    computed: {
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

    methods: {
        key(result: ReachabilityResult): string {
            return result.error ? `errors.${result.error}` : 'reachable';
        },

        status(result: ReachabilityResult): DiagnosticsStatus {
            return result.severity ? SEVERITY_STATUSES[result.severity] : 'passed';
        },
    },
});
