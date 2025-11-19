import './paypal-core-js';
import type PluginManager from 'src/plugin-system/plugin.manager';
import type TPayPalPluginError from '../base/paypal-plugin.error';
import type { ApplePaySession } from '@types/applepayjs';

declare global {
    type Products = 'default' | 'googlepay' | 'applepay' | 'acdc' | 'venmo';

    type PayPalPluginError = TPayPalPluginError;

    interface Window {
        PluginManager: PluginManager;
        ApplePayMerchandising: unknown;
        ApplePaySession: ApplePaySession;
    }
}
