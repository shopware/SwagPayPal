import Plugin from 'src/plugin-system/plugin.class';
import PayPalSdkLoader from '../helper/paypal-sdk-loader.helper';
import PayPalPluginError from './paypal-plugin.error';

export interface SwagPaypalBaseOptions {
    clientToken: string;
    environment: 'production' | 'sandbox';
    pageType?: PayPalCoreJS.PageTypes;
    partnerAttributionId: string;
    languageIso: string;
    currency: string;
    handleErrorUrl: string;
    partOfDomContentLoading: boolean;
}

/**
 * Base class for all PayPal plugins.
 *
 * On plugin initialization, the preparation flow is started, which loads the PayPal SDK and prepares the plugin.
 */
// @ts-expect-error - "private" _init is overriden
export default class SwagPaypalBase extends Plugin {
    static options: SwagPaypalBaseOptions = {
        /**
         * This option holds the client token required for field rendering
         */
        clientToken: '',

        /**
         * This option holds the client token required for field rendering
         */
        environment: 'production',

        /**
         * This option specifies the page type where the PayPal SDK is loaded
         */
        pageType: undefined,

        /**
         * This option holds the partner attribution id
         */
        partnerAttributionId: '',

        /**
         * This option specifies the language of the PayPal button
         */
        languageIso: 'en_GB',

        /**
         * This options specifies the currency of the PayPal button
         */
        currency: 'EUR',

        /**
         * URL for adding flash error message
         */
        handleErrorUrl: '',

        /**
         * This option toggles when the script should be loaded.
         * If false, the script will be loaded on 'load' instead of 'DOMContentLoaded' event.
         * See 'DOMContentLoaded' and 'load' event for more information.
         */
        partOfDomContentLoading: true,
    };

    private _instance: PayPalCoreJS.Instance | null = null;

    protected get instance(): PayPalCoreJS.Instance {
        if (!this._instance) {
            throw new Error('PayPal SDK instance not initialized yet');
        }

        return this._instance;
    }

    _init() {
        if (this.options.partOfDomContentLoading || document.readyState === 'complete') {
            // @ts-expect-error - "private" _init is called
            super._init();
        } else {
            // @ts-expect-error - "private" _init is called
            window.addEventListener('load', () => super._init());
        }
    }

    async init(): Promise<void> {
        await this.preparationFlow();
    }

    protected async preparationFlow(): Promise<void> {
        try {
            await this.beforePrepare();
            await this.prepare();
            await this.afterPrepare();
        } catch (error) {
            await this.handleError(PayPalPluginError.GENERIC_ERROR, true, error);
        }
    }

    /**
     * Hook called to load PayPal SDK and create SDK instance.
     * Override in child classes to handle pre-SDK loading logic.
     */
    protected async beforePrepare(): Promise<void> {
        this._instance = await PayPalSdkLoader.getSDK(
            {
                environment: this.options.environment,
                // debug: this.options.environment !== 'production',
            },
            {
                clientToken: this.options.clientToken,
                locale: this.options.languageIso.replace('_', '-'),
                pageType: this.options.pageType,
                partnerAttributionId: this.options.partnerAttributionId,
            },
        )
    }

    /**
     * Hook called when SDK is ready. Override in child classes to handle SDK initialization
     */
    protected async prepare(): Promise<void> {}

    protected async afterPrepare(): Promise<void> {}

    /**
     * @param code - The error code. Will be replaced by an extracted error code from {@link data} if available
     * @param fatal - A fatal error will not allow a rerender of the PayPal buttons
     * @param data - The error. Can be any type, but will be converted to a string
     */
    protected async handleError(code: string, fatal: boolean = false, data: unknown = undefined): Promise<void> {
        const error = PayPalPluginError.create(code, fatal, data);
        console.error(error.message);

        if (!this.options.handleErrorUrl) {
            return;
        }

        await fetch(this.options.handleErrorUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                code: error.code,
                error: error.message,
                fatal: error.isFatal,
                isCheckout: this.options.pageType === 'checkout',
            }),
        }).catch(console.error.bind(console, 'Failed to send error to server: '));

        await this.onErrorHandled(error);
    }

    /**
     * Will be called after the handleErrorUrl was called. See {@link handleError}.
     */
    protected onErrorHandled(error: PayPalPluginError): void|Promise<void> {
        if (this.options.pageType === 'checkout') {
            window.scrollTo(0, 0);
            window.location.reload();
        }
    }
}
