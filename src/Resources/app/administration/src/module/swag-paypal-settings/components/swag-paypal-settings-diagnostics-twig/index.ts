import type * as PayPal from 'SwagPayPal/types';
import {
    SEVERITY_STATUSES,
    type DiagnosticsStatus,
} from 'SwagPayPal/constant/swag-paypal-diagnostics.constant';
import template from './swag-paypal-settings-diagnostics-twig.html.twig';
import './swag-paypal-settings-diagnostics-twig.scss';

type TwigResult = PayPal.Diagnostics<'twig_result'>;
type TemplateOverride = PayPal.Diagnostics<'twig_template_override'>;

type Check =
    | { status: 'checking' }
    | { status: 'failed' }
    | { status: 'done'; result: TwigResult };

const AREAS: (keyof TwigResult)[] = ['storefront', 'documents'];

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
    } {
        return {
            checks: {},
        };
    },

    computed: {
        settingsStore() {
            return Shopware.Store.get('swagPayPalSettings');
        },

        check(): Check | null {
            return this.settingsStore.salesChannel ? this.checks[this.settingsStore.salesChannel] ?? null : null;
        },

        isChecking(): boolean {
            return this.check?.status === 'checking';
        },

        areas() {
            if (this.check?.status !== 'done') {
                return [];
            }

            const result = this.check.result;

            return AREAS.map((name) => ({
                name,
                applicable: result[name].applicable,
                overrides: result[name].overrides,
            }));
        },
    },

    watch: {
        'settingsStore.salesChannel': {
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

            try {
                const result = await this.SwagPayPalDiagnosticsService.twig(salesChannelId);

                this.checks[salesChannelId] = { status: 'done', result };
            } catch (errorResponse) {
                this.checks[salesChannelId] = { status: 'failed' };

                this.createNotificationError({
                    title: this.$t('swag-paypal-settings.diagnostics.twig.title'),
                    message: this.$t('swag-paypal-settings.diagnostics.twig.errorMessage', {
                        message: this.createMessageFromError(errorResponse as PayPal.ServiceError),
                    }),
                });
            }
        },

        onCheck() {
            if (this.settingsStore.salesChannel) {
                this.runCheck(this.settingsStore.salesChannel);
            }
        },

        overrideStatus(override: TemplateOverride): DiagnosticsStatus {
            return SEVERITY_STATUSES[override.severity];
        },

        location(override: TemplateOverride): string {
            // Templates stored in the database, like the ones of apps, have no file
            return `${override.file ?? override.templatePath}:${override.line}`;
        },

        paypalBlocks(override: TemplateOverride): string[] {
            const blocks = new Set(override.affected.map(({ block }) => block));
            if (override.block) {
                blocks.delete(override.block);
            }

            return [...blocks];
        },

        featureLabels(override: TemplateOverride): string {
            const features = new Set(override.affected.map(({ feature }) => feature));

            return [...features].map((feature) => {
                const snippet = `swag-paypal-settings.diagnostics.twig.features.${feature}`;

                return this.$te(snippet) ? this.$t(snippet) : feature;
            }).join(', ');
        },
    },
});
