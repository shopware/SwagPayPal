import { loadCoreSdkScript } from '../../node_modules/@paypal/paypal-js/dist/v6/esm/paypal-js.min.js';
import PayPalPluginError from '../base/paypal-plugin.error';

export default class PayPalLoader {
    private static paypal: Promise<PayPalCoreJS.Namespace>|null = null;

    private static instance: Promise<PayPalCoreJS.Instance>|null = null;

    private static eligibleMethods: Promise<PayPalCoreJS.FindEligibleMethods.EligiblePaymentMethods>|null = null;

    private static messagesScript: Promise<void>|null = null;

    private static googlePay: Promise<void>|null = null;

    private static applePay: Promise<void>|null = null;

    private constructor() {}

    public static async getInstance(script: PayPalCoreJS.LoadCoreScriptOptions, options: PayPalCoreJS.InstanceOptions): Promise<PayPalCoreJS.Instance> {
        try {
            PayPalLoader.instance ??= this.loadPayPalCore(script)!.then((paypal) => paypal.createInstance({
                ...options,
                components: [
                    'paypal-payments',
                    'venmo-payments',
                    'card-fields',
                    'googlepay-payments',
                    'applepay-payments',
                    'paypal-messages',
                ],
            }));

            return await PayPalLoader.instance;
        } catch (error) {
            PayPalLoader.instance = null;
            throw PayPalPluginError.scriptError(error);
        }
    }

    public static async loadPayPalCore(script: PayPalCoreJS.LoadCoreScriptOptions): Promise<PayPalCoreJS.Namespace> {
        try {
            PayPalLoader.paypal ??= loadCoreSdkScript(script) as Promise<PayPalCoreJS.Namespace>;
            return await PayPalLoader.paypal!;
        } catch (error) {
            PayPalLoader.paypal = null;
            throw PayPalPluginError.scriptError(error);
        }
    }

    public static async loadGooglePay(): Promise<void> {
        try {
            PayPalLoader.googlePay ??= PayPalLoader.loadCustomScript(
                new URL('https://pay.google.com/gp/p/js/pay.js'),
                () => !!window?.google?.payments?.api?.PaymentsClient,
            );
            return await PayPalLoader.googlePay;
        } catch (error) {
            PayPalLoader.googlePay = null;
            throw PayPalPluginError.scriptError(error);
        }
    }

    public static async loadApplePay(): Promise<void> {
        try {
            PayPalLoader.applePay ??= PayPalLoader.loadCustomScript(
                new URL('https://applepay.cdn-apple.com/jsapi/v1/apple-pay-sdk.js'),
                () => !!window.ApplePayMerchandising,
            );
            return await PayPalLoader.applePay;
        } catch (error) {
            PayPalLoader.applePay = null;
            throw PayPalPluginError.scriptError(error);
        }
    }

    public static async findEligibleMethods(options: PayPalCoreJS.FindEligibleMethods.Options): Promise<PayPalCoreJS.FindEligibleMethods.EligiblePaymentMethods> {
        if (!PayPalLoader.instance) {
            throw new Error('PayPal SDK instance is not initialized yet.');
        }

        PayPalLoader.eligibleMethods ??= PayPalLoader.instance.then((instance) => instance.findEligibleMethods(options));

        try {
            return await PayPalLoader.eligibleMethods;
        } catch (error) {
            PayPalLoader.eligibleMethods = null;
            throw PayPalPluginError.genericError(false, 'Failed to find eligible payment methods');
        }
    }

    private static async loadCustomScript(url: URL, checkLoaded: () => boolean): Promise<void> {
        const currentScript = document.querySelector<HTMLScriptElement>(`script[src*="${url.toString()}"]`);

        if (currentScript) {
            if (checkLoaded()) {
                return Promise.resolve();
            }

            return new Promise<void>((resolve, reject) => {
                currentScript.addEventListener('load', () => resolve());
                currentScript.addEventListener('error', (event) => reject(event));
            });
        }

        return new Promise<void>((resolve, reject) => {
            const scriptTag = document.createElement('script');
            scriptTag.src = url.toString();
            scriptTag.async = true;
            scriptTag.type = 'text/javascript';

            scriptTag.addEventListener('load', () => resolve());
            scriptTag.addEventListener('error', (event) => reject(event.error));

            document.head.appendChild(scriptTag);
        })
    }
}
