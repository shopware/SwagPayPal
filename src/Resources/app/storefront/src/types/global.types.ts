import type TPayPalPluginError from '../base/paypal-plugin.error';

// The extension validation rejects import declarations from src/plugin-system, type-only ones included
/* eslint-disable @typescript-eslint/consistent-type-imports */
type PluginClass = typeof import('src/plugin-system/plugin.class').default;
type PluginManagerClass = typeof import('src/plugin-system/plugin.manager').default;
/* eslint-enable @typescript-eslint/consistent-type-imports */

declare global {
    type OmitReadonly<T> = { -readonly [P in keyof T]: OmitReadonly<T[P]> };

    type Products = 'spb' | 'googlepay' | 'applepay' | 'acdc' | 'venmo';

    type PayPalPluginError = TPayPalPluginError;

    type SwPlugin = InstanceType<PluginClass>;

    interface ApplePay {
        ApplePayError?: ApplePayError;
        ApplePaySDK?: unknown;
        ApplePayWebOptions?: unknown;
        ApplePaySession?: ApplePaySession&(typeof ApplePaySession);
    }

    interface Window extends ApplePay {
        PluginManager: InstanceType<PluginManagerClass>&PluginManagerClass;
        PluginBaseClass: PluginClass;
    }
}

declare module '@paypal/paypal-js/types' {
    // Declaration merging only works with an interface
    // eslint-disable-next-line @typescript-eslint/no-empty-object-type
    interface PayPalNamespace extends PayPalCoreJS.Namespace {
    }
}
