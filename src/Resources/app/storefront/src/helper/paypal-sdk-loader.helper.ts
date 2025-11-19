import { loadCoreSdkScript } from '../../node_modules/@paypal/paypal-js/dist/v6/esm/paypal-js.min.js';
// import { loadCoreSdkScript } from '@paypal/paypal-js/sdk-v6';
import PayPalPluginError from '../base/paypal-plugin.error';

export default class PayPalSdkLoader {
    private static self: PayPalSdkLoader|null = null;

    private paypal: Promise<PayPalCoreJS.Namespace>|null = null;

    private sdkInstance: Promise<PayPalCoreJS.Instance>|null = null;

    private eligibleMethods: Promise<PayPalCoreJS.FindEligibleMethods.EligiblePaymentMethods>|null = null;

    private messagesScript: Promise<void>|null = null;

    private constructor() {}

    public static async getSDK(script: PayPalCoreJS.LoadCoreScriptOptions, options: PayPalCoreJS.InstanceOptions): Promise<PayPalCoreJS.Instance> {
        if (!PayPalSdkLoader.self) {
            PayPalSdkLoader.self = new PayPalSdkLoader();
        }

        try {
            PayPalSdkLoader.self.paypal ??= loadCoreSdkScript(script);
            await PayPalSdkLoader.self.paypal;
        } catch (error) {
            PayPalSdkLoader.self.paypal = null;
            throw PayPalPluginError.scriptError(error);
        }

        try {
            PayPalSdkLoader.self.sdkInstance ??= PayPalSdkLoader.self.paypal!.then((sdk) => sdk.createInstance({
                ...options,
                components: [
                    'paypal-payments',
                    'venmo-payments',
                    'card-fields',
                    'googlepay-payments',
                    'applepay-payments',
                ],
            }));

            return await PayPalSdkLoader.self.sdkInstance;
        } catch (error) {
            PayPalSdkLoader.self.sdkInstance = null;
            throw PayPalPluginError.scriptError(error);
        }
    }

    public static async findEligibleMethods(options: PayPalCoreJS.FindEligibleMethods.Options): Promise<PayPalCoreJS.FindEligibleMethods.EligiblePaymentMethods> {
        if (!PayPalSdkLoader.self?.sdkInstance) {
            throw new Error('PayPal SDK instance is not initialized yet.');
        }

        PayPalSdkLoader.self.eligibleMethods ??= PayPalSdkLoader.self.sdkInstance.then((instance) => instance.findEligibleMethods(options));

        try {
            return await PayPalSdkLoader.self.eligibleMethods;
        } catch (error) {
            PayPalSdkLoader.self.eligibleMethods = null;
            throw PayPalPluginError.genericError(false, 'Failed to find eligible payment methods');
        }
    }

    public static loadMessagesScript(environment: 'sandbox' | 'production'): Promise<void> {
        if (!PayPalSdkLoader.self) {
            PayPalSdkLoader.self = new PayPalSdkLoader();
        }

        PayPalSdkLoader.self.messagesScript ??= PayPalSdkLoader.loadCustomScript('/web-sdk/v6/paypal-messages', environment);

        return PayPalSdkLoader.self.messagesScript;
    }

    private static async loadCustomScript(path: string, environment: 'sandbox' | 'production'): Promise<void> {
        return new Promise<void>((resolve, reject) => {
            const url = new URL(path, environment === 'production' ? 'https://www.paypal.com' : 'https://www.sandbox.paypal.com');

            const scriptTag = document.createElement('script');
            scriptTag.src = url.toString();
            scriptTag.async = true;
            scriptTag.type = 'text/javascript';

            scriptTag.addEventListener('load', () => resolve());
            scriptTag.addEventListener('error', () => reject(PayPalPluginError.scriptError('Failed to load paypal messages script')));

            document.head.appendChild(scriptTag);
        })
    }
}
