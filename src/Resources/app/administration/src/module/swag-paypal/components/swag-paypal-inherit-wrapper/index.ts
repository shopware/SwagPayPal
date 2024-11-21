import type * as PayPal from 'src/types';
import { SystemConfigDefinition } from '../../../../types/system-config';
import template from './swag-paypal-inherit-wrapper.html.twig';

/**
 * @private
 */
export default Shopware.Component.wrapComponentConfig({
    template,

    props: {
        path: {
            required: true,
            type: String as PropType<keyof PayPal.SystemConfig>,
        },
        actualConfigData: {
            type: Object as PropType<PayPal.SystemConfig>,
            required: true,
            default: () => { return { null: {} }; },
        },
        allConfigs: {
            type: Object as PropType<Record<string, PayPal.SystemConfig>>,
            required: true,
        },
        selectedSalesChannelId: {
            type: String,
            required: false,
            default: null,
        },
    },

    data(): {
        value: PayPal.SystemConfig[typeof this.path];
    } {
        return {
            value: this.actualConfigData[this.path],
        };
    },

    computed: {
        customInheritationCheckFunction() {
            switch (SystemConfigDefinition[this.path]) {
                case 'array': return (value: unknown) => !Array.isArray(value);
                case 'boolean': return (value: unknown) => typeof value !== 'boolean';
                case 'string': return (value: unknown) => typeof value !== 'string';
                default: throw new Error(`Unhandled or undefined definition for system-config path "${this.path}"`);
            }
        },

        inheritedValue(): PayPal.SystemConfig[typeof this.path] | null {
            return this.selectedSalesChannelId ? this.allConfigs.null[this.path] ?? null : null;
        },

        hasParent() {
            return !!this.selectedSalesChannelId;
        },

        attrs() {
            return Shopware.Utils.object.pick(this.$attrs, [
                'label',
                'helpText',
                'error',
                'required',
                'disabled',
            ]);
        },
    },

    watch: {
        actualConfigData: {
            handler(actualConfigData: PayPal.SystemConfig) {
                this.value = actualConfigData[this.path];
            },
            deep: true,
        },
    },

    methods: {
        setValue(value: PayPal.SystemConfig[typeof this.path]) {
            // @ts-expect-error
            this.actualConfigData[this.path] = value;
            this.value = value;
        },
    },
});
