import Plugin from 'src/plugin-system/plugin.class';
import PayPalLoader from '../helper/paypal-loader.helper';
import PayPalPluginError from './paypal-plugin.error';

export interface SwagPaypalBaseOptions {
    clientToken: string;
    environment: 'production' | 'sandbox';
    pageType?: PayPalCoreJS.PageTypes;
    partnerAttributionId: string;
    languageIso: string;
    currency: string;
    handleErrorUrl: string;
}

/**
 * Base class for all PayPal plugins.
 *
 * On plugin initialization, the preparation flow is started, which loads the PayPal SDK and prepares the plugin.
 */
export default abstract class SwagPaypalBase extends Plugin {
    declare options: SwagPaypalBaseOptions;

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
    };

    protected instance: PayPalCoreJS.Instance<(typeof this.metadata)['components'][number]> | null = null;

    protected static eligibleMethods: Promise<PayPalCoreJS.FindEligibleMethods.EligiblePaymentMethods> | null = null;

    protected abstract get metadata(): { components: PayPalCoreJS.Components[] };

    init(): void {
        this.preparationFlow();
    }

    protected async preparationFlow(): Promise<void> {
        try {
            await this.beforePrepare();
            await this.prepare();
            await this.afterPrepare();
        } catch (error) {
            await this.handleError(PayPalPluginError.SCRIPT_ERROR, true, error);
        }
    }

    /**
     * Hook called to load PayPal SDK and create SDK instance.
     * Override in child classes to handle pre-SDK loading logic.
     */
    protected async beforePrepare(): Promise<void> {
        const paypal = await PayPalLoader.loadPayPalCore({ environment: this.options.environment });

        this.instance ??= await paypal.createInstance({
            clientToken: this.options.clientToken,
            locale: this.options.languageIso.replace('_', '-'),
            pageType: this.options.pageType,
            partnerAttributionId: this.options.partnerAttributionId,
            components: this.metadata.components,
        });
    }

    /**
     * Hook called when SDK is ready. Override in child classes to handle SDK initialization
     */
    protected prepare(): Promise<void>|void {}

    protected afterPrepare(): Promise<void>|void {}

    public async findEligibleMethods(): Promise<PayPalCoreJS.FindEligibleMethods.EligiblePaymentMethods> {
        if (!this.instance) {
            throw new Error('PayPal SDK instance is not initialized yet.');
        }

        SwagPaypalBase.eligibleMethods ??= this.instance.findEligibleMethods({
            currencyCode: this.options.currency,
        });

        try {
            return await SwagPaypalBase.eligibleMethods;
        } catch (error) {
            SwagPaypalBase.eligibleMethods = null;
            throw PayPalPluginError.scriptError('Failed to find eligible payment methods');
        }
    }

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
                plugin: this.constructor.name,
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
