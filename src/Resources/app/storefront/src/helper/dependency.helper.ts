import PayPalPluginError from '../base/paypal-plugin.error';

const PAYPAL_SDK_NAMESPACE = 'paypalV6';

export default class DependencyHelper {
    private static paypal: Promise<void>|null = null;

    private static googlePay: Promise<void>|null = null;

    private static applePay: Promise<void>|null = null;

    private constructor() {}

    public static async loadPayPalCore(script: { environment: 'production' | 'sandbox' }): Promise<PayPalCoreJS.Namespace> {
        try {
            const url = new URL('/web-sdk/v6/core', script.environment === 'sandbox' ? 'https://www.sandbox.paypal.com' : 'https://www.paypal.com');

            DependencyHelper.paypal ??= DependencyHelper.loadCustomScript(
                url,
                () => !!this._getPaypalNamespace(url),
                { 'data-namespace': PAYPAL_SDK_NAMESPACE },
            );

            await DependencyHelper.paypal;

            const paypal = this._getPaypalNamespace(url);
            if (!paypal) {
                throw new Error('Script loaded but check failed');
            }

            return paypal;
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

    private static async loadCustomScript(url: URL, checkLoaded: () => boolean, attributes: Record<string, string> = {}): Promise<void> {
        const currentScript = document.querySelector<HTMLScriptElement>(`script[src*="${url.toString()}"]`);

        if (currentScript) {
            if (checkLoaded()) {
                return Promise.resolve();
            }

            return new Promise<void>((resolve, reject) => {
                currentScript.addEventListener('load', () => resolve(), { once: true });
                // eslint-disable-next-line @typescript-eslint/prefer-promise-reject-errors
                currentScript.addEventListener('error', (event) => reject(event.error), { once: true });
            });
        }

        return new Promise<void>((resolve, reject) => {
            const scriptTag = document.createElement('script');
            scriptTag.src = url.toString();
            scriptTag.async = true;
            scriptTag.type = 'text/javascript';
            for (const [key, value] of Object.entries(attributes)) {
                scriptTag.setAttribute(key, value);
            }

            scriptTag.addEventListener('load', () => {
                if (checkLoaded()) {
                    return resolve();
                }

                // eslint-disable-next-line @typescript-eslint/prefer-promise-reject-errors
                reject(`Script "${url.toString()}" loaded but check failed.`);
            }, { once: true });
            // eslint-disable-next-line @typescript-eslint/prefer-promise-reject-errors
            scriptTag.addEventListener('error', (event) => reject(event.error), { once: true });

            document.head.appendChild(scriptTag);
        });
    }

    /**
     * Returns the paypal v6 namespace by any means:
     * - v6 loaded via our own or another namespace
     * - v6 loaded via a v5 occupied namespace
     */
    private static _getPaypalNamespace(url: URL): PayPalCoreJS.Namespace | null {
        const namespace = document.querySelector<HTMLScriptElement>(`script[src*="${url}"][data-namespace="${PAYPAL_SDK_NAMESPACE}"]`)?.dataset.namespace
            ?? document.querySelector<HTMLScriptElement>(`script[src*="${url}"]`)?.dataset.namespace ?? 'paypal';

        // @ts-expect-error - window is indexed by a dynamic namespace string
        const paypal = (window[namespace]?.v6 ?? window[namespace]) as Partial<PayPalCoreJS.Namespace> | undefined;

        if (!!paypal?.version?.startsWith('6') && typeof paypal.createInstance === 'function') {
            return paypal as PayPalCoreJS.Namespace;
        }

        return null;
    }
}
