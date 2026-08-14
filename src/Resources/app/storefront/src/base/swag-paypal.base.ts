import Plugin from 'src/plugin-system/plugin.class';
import DependencyHelper from '../helper/dependency.helper';
import PayPalPluginError from './paypal-plugin.error';
import { RequestHelper } from '../helper/request.helper';

export interface SwagPaypalBaseOptions {
    clientId: string | null;
    merchantPayerId: string | null;
    clientToken: string | null;
    clientTokenUrl: string;
    environment: 'production' | 'sandbox';
    pageType?: PayPalCoreJS.PageTypes;
    partnerAttributionId: string;
    languageIso: string;
    currency: string;
    handleErrorUrl: string;
}

type Credentials = { clientId: string; merchantId: string } | { clientToken: string };

/**
 * Base class for all PayPal plugins.
 *
 * On plugin initialization, the preparation flow is started, which loads the PayPal SDK and prepares the plugin.
 */
export default abstract class SwagPaypalBase extends Plugin {
    declare options: SwagPaypalBaseOptions;

    static options: SwagPaypalBaseOptions = {
        /**
         * This option holds the client id for the PayPal SDK
         */
        clientId: null,

        /**
         * This option holds the merchant payer id for the PayPal SDK
         */
        merchantPayerId: null,

        /**
         * This option holds the client token required for field rendering
         */
        clientToken: null,

        /**
         * This option holds the url to fetch the client token if
         * {@link clientToken} or {@link clientId} and {@link merchantPayerId} are not provided
         */
        clientTokenUrl: '',

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

    protected instance: PayPalCoreJS.SdkInstance<(typeof this.metadata)['components']> | null = null;

    protected static eligibleMethods: Promise<PayPalCoreJS.FindEligibleMethods.EligiblePaymentMethods> | null = null;

    protected static clientToken: Promise<string> | null = null;

    protected abstract get metadata(): { components: PayPalCoreJS.Components[] };

    init(): void {
        this.setupFlow();
    }

    protected async setupFlow(): Promise<void> {
        try {
            await this.beforeSetup();
            await this.setup();
            await this.afterSetup();
        } catch (error) {
            await this.handleError(PayPalPluginError.setupFlow(PayPalPluginError.CODE_GENERIC, error));
        }
    }

    /**
     * Hook called to load PayPal SDK and create SDK instance.
     * Override in child classes to handle pre-SDK loading logic.
     */
    protected async beforeSetup(): Promise<void> {
        const [credentials, paypal] = await Promise.all([
            this._fetchCredentials(),
            DependencyHelper.loadPayPalCore({ environment: this.options.environment }),
        ]);

        this.instance ??= await paypal.createInstance({
            ...credentials,
            locale: this.options.languageIso.replace('_', '-'),
            pageType: this.options.pageType,
            partnerAttributionId: this.options.partnerAttributionId,
            components: this.metadata.components,
        });
    }

    /**
     * Hook called when SDK is ready. Override in child classes to handle SDK initialization
     */
    protected setup(): Promise<void>|void {}

    protected afterSetup(): Promise<void>|void {}

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
            throw PayPalPluginError.create(PayPalPluginError.CODE_SCRIPT, null, `Failed to find eligible payment methods: ${PayPalPluginError.stringifyError(error)}`);
        }
    }

    /**
     * @param code - The error code. Will be replaced by an extracted error code from {@link data} if available
     * @param fatal - A fatal error will not allow a rerender of the PayPal buttons
     * @param data - The error. Can be any type, but will be converted to a string
     */
    protected async handleError(error: PayPalPluginError): Promise<void> {
        console.error(this._pluginName, error);

        if (!this.options.handleErrorUrl) {
            return;
        }

        await RequestHelper.fetch(this.options.handleErrorUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                step: error.step,
                code: error.code,
                error: error.message,
                fatal: error.isFatal,
                plugin: this._pluginName,
                pageType: this.options.pageType,
            }),
        }).catch(console.error.bind(console, 'Failed to send error to server: '));

        await this.afterHandleError(error);
    }

    /**
     * Will be called after the handleErrorUrl was called. See {@link handleError}.
     */
    protected afterHandleError(error: PayPalPluginError): void|Promise<void> {
        if (this.options.pageType === 'checkout') {
            window.scrollTo(0, 0);
            window.location.reload();
        }
    }

    protected async _fetchClientToken(): Promise<string> {
        if (this.options.clientToken) {
            SwagPaypalBase.clientToken ??= Promise.resolve(this.options.clientToken);

            return this.options.clientToken;
        }

        const response = await RequestHelper.fetch(this.options.clientTokenUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
        });

        if (!response.ok) {
            throw await PayPalPluginError.api('client-token', response);
        }

        const { token } = await response.json() as { token: string };
        return token;
    }

    protected async _fetchCredentials(): Promise<Credentials> {
        if (this.options.clientId && this.options.merchantPayerId) {
            return { clientId: this.options.clientId, merchantId: this.options.merchantPayerId };
        }

        SwagPaypalBase.clientToken ??= this._fetchClientToken();
        return { clientToken: await SwagPaypalBase.clientToken };
    }
}
