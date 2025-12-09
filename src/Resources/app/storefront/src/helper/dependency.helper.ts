import PayPalPluginError from '../base/paypal-plugin.error';

export default class DependencyHelper {
    private static paypal: Promise<void>|null = null;

    private static googlePay: Promise<void>|null = null;

    private static applePay: Promise<void>|null = null;

    private constructor() {}

    public static async loadPayPalCore(script: PayPalCoreJS.LoadCoreScriptOptions): Promise<void> {
        try {
            DependencyHelper.paypal ??= DependencyHelper.loadCustomScript(
                new URL('/web-sdk/v6/core', script.environment === 'sandbox' ? 'https://www.sandbox.paypal.com' : 'https://www.paypal.com'),
                () => !!window.paypal?.createInstance,
            );
            return await DependencyHelper.paypal;
        } catch (error) {
            DependencyHelper.paypal = null;
            throw PayPalPluginError.scriptLoad('paypal-core-js', error);
        }
    }

    public static async loadGooglePay(): Promise<void> {
        try {
            DependencyHelper.googlePay ??= DependencyHelper.loadCustomScript(
                new URL('https://pay.google.com/gp/p/js/pay.js'),
                () => !!window?.google?.payments?.api?.PaymentsClient,
            );
            return await DependencyHelper.googlePay;
        } catch (error) {
            DependencyHelper.googlePay = null;
            throw PayPalPluginError.scriptLoad('google-pay-js', error);
        }
    }

    public static async loadApplePay(): Promise<void> {
        try {
            DependencyHelper.applePay ??= DependencyHelper.loadCustomScript(
                new URL('https://applepay.cdn-apple.com/jsapi/1.latest/apple-pay-sdk.js'),
                () => !!window.ApplePaySDK,
            );
            return await DependencyHelper.applePay;
        } catch (error) {
            DependencyHelper.applePay = null;
            throw PayPalPluginError.scriptLoad('apple-pay-js', error);
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
                // eslint-disable-next-line @typescript-eslint/prefer-promise-reject-errors
                currentScript.addEventListener('error', (event) => reject(event.error));
            });
        }

        return new Promise<void>((resolve, reject) => {
            const scriptTag = document.createElement('script');
            scriptTag.src = url.toString();
            scriptTag.async = true;
            scriptTag.type = 'text/javascript';

            scriptTag.addEventListener('load', () => {
                if (checkLoaded()) {
                    return resolve();
                }

                // eslint-disable-next-line @typescript-eslint/prefer-promise-reject-errors
                reject(`Script "${url.toString()}" loaded but check failed.`);
            });
            // eslint-disable-next-line @typescript-eslint/prefer-promise-reject-errors
            scriptTag.addEventListener('error', (event) => reject(event.error));

            document.head.appendChild(scriptTag);
        });
    }
}
