import type { DiagnosticsStatus } from 'SwagPayPal/constant/swag-paypal-diagnostics.constant';
import template from './swag-paypal-settings-diagnostics-item.html.twig';
import './swag-paypal-settings-diagnostics-item.scss';

const ICONS: Record<DiagnosticsStatus, string> = {
    error: 'solid-times-circle',
    warning: 'solid-exclamation-triangle',
    info: 'solid-info-circle',
    passed: 'solid-check-circle',
    'not-observed': 'solid-minus-circle',
};

/**
 * `compact` leaves out the badge, for results inside another finding.
 * The `subtitle` slot replaces the `subtitle` prop, for a link inside the subtitle.
 *
 * @private
 */
export default Shopware.Component.wrapComponentConfig({
    template,

    props: {
        status: {
            type: String as PropType<DiagnosticsStatus>,
            required: true,
        },

        title: {
            type: String,
            required: true,
        },

        subtitle: {
            type: String,
            required: false,
            default: '',
        },

        compact: {
            type: Boolean,
            required: false,
            default: false,
        },
    },

    computed: {
        icon(): string {
            return ICONS[this.status];
        },
    },

    methods: {
        // Slots aren't reactive, so a computed property could keep a stale value
        hasContent(): boolean {
            return !!this.$slots.default;
        },
    },
});
