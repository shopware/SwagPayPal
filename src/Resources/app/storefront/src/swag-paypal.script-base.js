import Plugin from 'src/plugin-system/plugin.class';
import { loadScript } from '@paypal/paypal-js';

const availableAPMs = [
    'card',
    'bancontact',
    'blik',
    'eps',
    'giropay',
    'ideal',
    'mybank',
    'p24',
    'sepa',
    'sofort',
    'venmo',
];

export default class SwagPayPalScriptBase extends Plugin {
    static options = {
        /**
         * This option holds the client id specified in the settings
         *
         * @type string
         */
        clientId: '',

        /**
         * This option holds the client token required for field rendering
         *
         * @type string
         */
        clientToken: '',

        /**
         * This option holds the merchant id specified in the settings
         *
         * @type string
         */
        merchantPayerId: '',

        /**
         * This option holds the partner attribution id
         *
         * @type string
         */
        partnerAttributionId: '',

        /**
         * This options specifies the currency of the PayPal button
         *
         * @type string
         */
        currency: 'EUR',

        /**
         * This options defines the payment intent
         *
         * @type string
         */
        intent: 'capture',

        /**
         * This option toggles the PayNow/Login text at PayPal
         *
         * @type boolean
         */
        commit: true,

        /**
         * This option specifies the language of the PayPal button
         *
         * @type string
         */
        languageIso: 'en_GB',

        /**
         * This option toggles if the pay later button should be shown
         *
         * @type boolean
         */
        showPayLater: true,

        /**
         * This option toggles if credit card and ELV should be shown
         *
         * @type boolean
         */
        useAlternativePaymentMethods: true,

        /**
         * This option specifies if selected APMs should be hidden
         *
         * @type string[]
         */
        disabledAlternativePaymentMethods: [],

        /**
         * User ID token for vaulting
         *
         * @type string|null
         */
        userIdToken: null,

        /**
         * This option will await the visibility of the element before continue loading the script.
         * Useful for listing pages to not load all express buttons at once.
         *
         * @type boolean
         */
        scriptAwaitVisibility: false,

        /**
         * This option toggles when the script should be loaded.
         * If false, the script will be loaded on 'load' instead of 'DOMContentLoaded' event.
         * See 'DOMContentLoaded' and 'load' event for more information.
         *
         * @type boolean
         */
        partOfDomContentLoading: true,
    };

    static scriptPromises = {};

    static paypal = {};

    _init() {
        if (this.options.partOfDomContentLoading || document.readyState === 'complete') {
            super._init();
        } else {
            window.addEventListener('load', () => {
                super._init();
            });
        }
    }

    get scriptOptionsHash() {
        return JSON.stringify(this.getScriptOptions());
    }

    async createScript(callback) {
        SwagPayPalScriptBase.scriptPromises[this.scriptOptionsHash] ??= this._loadScript();

        const wrapper = async () => {
            callback(await SwagPayPalScriptBase.scriptPromises[this.scriptOptionsHash]);
        };

        if (this.options.scriptAwaitVisibility) {
            await this._awaitVisibility(wrapper);
        } else {
            await wrapper();
        }
    }

    async _awaitVisibility(callback) {
        const observer = new IntersectionObserver(([entry]) => {
            if (entry.isIntersecting) {
                observer.disconnect();
                callback();
            }
        }, {
            rootMargin: '200px', // Load the buttons before they become visible
        });

        observer.observe(this.el);
    }

    async _loadScript() {
        SwagPayPalScriptBase.paypal[this.scriptOptionsHash] = await loadScript(this.getScriptOptions());

        // overwriting an existing `window.paypal` object would remove previously rendered elements
        // therefore we remove it so other scripts can load it on their own
        delete window.paypal;

        return SwagPayPalScriptBase.paypal[this.scriptOptionsHash];
    }

    /**
     * The options the PayPal script will be loaded with.
     * Make sure to not create a flaky order of options, as this will
     * mess up the `scriptOptionsHash` and therefore affects script caching.
     */
    getScriptOptions() {
        const config = {
            components: 'buttons,messages,card-fields,funding-eligibility,applepay,googlepay',
            'client-id': this.options.clientId,
            commit: !!this.options.commit,
            locale: this.options.languageIso,
            currency: this.options.currency,
            intent: this.options.intent,
            'enable-funding': 'paylater,venmo',
        };

        if (this.options.disablePayLater || this.options.showPayLater === false) {
            config['enable-funding'] = 'venmo';
        }

        if (this.options.useAlternativePaymentMethods === false) {
            config['disable-funding'] = availableAPMs.join(',');
        } else if (this.options.disabledAlternativePaymentMethods.length > 0) {
            config['disable-funding'] = this.options.disabledAlternativePaymentMethods.join(',');
        }

        if (this.options.merchantPayerId) {
            config['merchant-id'] = this.options.merchantPayerId;
        }

        if (this.options.clientToken) {
            config['data-client-token'] = this.options.clientToken;
        }

        if (this.options.userIdToken) {
            config['data-user-id-token'] = this.options.userIdToken;
        }

        if (this.options.partnerAttributionId) {
            config['data-partner-attribution-id'] = this.options.partnerAttributionId;
        }

        return config;
    }
}
