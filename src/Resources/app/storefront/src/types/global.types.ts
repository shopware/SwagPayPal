import type PluginManager from 'src/plugin-system/plugin.manager';
import type TPayPalPluginError from '../base/paypal-plugin.error';
import type Plugin from 'src/plugin-system/plugin.class';

declare global {
    type OmitReadonly<T> = { -readonly [P in keyof T]: OmitReadonly<T[P]> };

    type Products = 'spb' | 'googlepay' | 'applepay' | 'acdc' | 'venmo';

    type PayPalPluginError = TPayPalPluginError;

    type SwPlugin = Plugin;

    interface ApplePay {
        ApplePayError?: ApplePayError;
        ApplePaySDK?: unknown;
        ApplePayWebOptions?: unknown;
        ApplePaySession?: ApplePaySession&(typeof ApplePaySession);
    }

    interface Window extends ApplePay {
        PluginManager: PluginManager&(typeof PluginManager);
    }
}

declare module '@paypal/paypal-js/types' {
    interface PayPalNamespace extends PayPalCoreJS.Namespace {
    }
}
