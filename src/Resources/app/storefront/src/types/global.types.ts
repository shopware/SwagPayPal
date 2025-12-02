import type PluginManager from 'src/plugin-system/plugin.manager';
import type TPayPalPluginError from '../base/paypal-plugin.error';
import type Plugin from 'src/plugin-system/plugin.class';

declare global {
    type Products = 'default' | 'googlepay' | 'applepay' | 'acdc' | 'venmo';

    type PayPalPluginError = TPayPalPluginError;

    type SwPlugin = Plugin;

    interface Window {
        PluginManager: PluginManager&(typeof PluginManager);
        ApplePayMerchandising: unknown;
        ApplePaySession?: ApplePaySession&(typeof ApplePaySession);
    }
}

declare module '@paypal/paypal-js/types' {
    interface PayPalNamespace extends PayPalCoreJS.Namespace {}
}
