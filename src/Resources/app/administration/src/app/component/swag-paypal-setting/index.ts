import type * as PayPal from 'SwagPayPal/types';
import template from './swag-paypal-setting.html.twig';
import './swag-paypal-setting.scss';
import { SystemConfigDefinition } from '../../../types/system-config';
import { type SYSTEM_CONFIG, SYSTEM_CONFIGS } from '../../../constant/swag-paypal-settings.constant';

const { string, object } = Shopware.Utils;

/**
 * @private - The component has a stable public API (props), but expect that implementation details may change.
 */
export default Shopware.Component.wrapComponentConfig({
    template,

    inject: ['acl'],

    emits: ['update:value'],

    props: {
        path: {
            required: true,
            type: String as PropType<SYSTEM_CONFIG>,
            validation: (value: SYSTEM_CONFIG) => {
                return SYSTEM_CONFIGS.includes(value);
            },
        },
    },

    computed: {
        settingsStore() {
            return Shopware.Store.get('swagPayPalSettings');
        },

        value() {
            return this.settingsStore.getActual(this.path);
        },

        inheritedValue() {
            return this.settingsStore.salesChannel
                ? this.settingsStore.getRoot(this.path)
                : undefined;
        },

        hasParent() {
            return !!this.settingsStore.salesChannel;
        },

        pathDomainless() {
            return this.path.replace('SwagPayPal.settings.', '');
        },

        disabled(): boolean {
            return !this.acl.can('swag_paypal.editor')
                || this.settingsStore.isLoading
                || !!this.formAttrs.disabled;
        },

        type() {
            return SystemConfigDefinition[this.path];
        },

        customInheritationCheckFunction() {
            return (value: unknown) => value === null || value === undefined;
        },

        label() {
            return this.tif(`swag-paypal-setting.label.${this.pathDomainless}`);
        },

        helpText() {
            return this.tif(
                `swag-paypal-setting.helpText.${this.pathDomainless}.${this.settingsStore.getActual(this.path)}`,
                `swag-paypal-setting.helpText.${this.pathDomainless}`,
            );
        },

        hintText() {
            return this.tif(
                `swag-paypal-setting.hintText.${this.pathDomainless}.${this.settingsStore.getActual(this.path)}`,
                `swag-paypal-setting.hintText.${this.pathDomainless}`,
            );
        },

        attrs() {
            // normalize attribute keys to camelCase
            const entries = Object.entries(this.$attrs).map(([key, value]) => [string.camelCase(key), value]);
            const attrs = Object.fromEntries(entries) as Record<string, unknown>;

            if (!attrs.hasOwnProperty('label') && this.label) {
                attrs.label = this.label;
            }

            if (!attrs.hasOwnProperty('helpText') && this.helpText) {
                attrs.helpText = this.helpText;
            }

            if (!attrs.hasOwnProperty('hintText') && this.hintText) {
                attrs.hintText = this.hintText;
            }

            return attrs;
        },

        wrapperAttrs(): Record<string, unknown> {
            return object.pick(this.attrs, [
                'disabled',
                'helpText',
                'label',
                'required',
            ]);
        },

        formAttrs(): Record<string, unknown> {
            return object.pick(this.attrs, [
                'disabled',
                'error',
                'labelProperty',
                'options',
                'valueProperty',
            ]);
        },

        wrapperClasses(): Record<string, boolean> {
            return {
                'is--bordered': this.type === 'boolean' && (this.attrs.bordered ?? true),
                [`is--${this.type}`]: true,
            };
        },
    },

    methods: {
        /**
         * Translate if found, otherwise return null
         */
        tif(...keys: string[]): string | null {
            // $te will also report partial matches as found
            const key = keys.find((k) => this.$te(k) && this.$t(k) !== k);

            return key ? this.$t(key) : null;
        },

        setValue(value: PayPal.SystemConfig[keyof PayPal.SystemConfig]) {
            if (value !== this.value) {
                this.settingsStore.set(this.path, value);
                this.$emit('update:value', value);
            }
        },
    },
});
