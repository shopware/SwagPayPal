import Plugin from 'src/plugin-system/plugin.class';
import { loadScript } from '@paypal/paypal-js';
import SwagPayPalScriptLoading from './swag-paypal.script-loading';

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
    /**
     * @deprecated tag:v10.0.0 - will be removed without replacement
     */
    static scriptLoading = new SwagPayPalScriptLoading();

    static options = {
        /**
         * This option holds the client id specified in the settings
         *
         * @type string
         */
        clientId: '',

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

        this._createScriptLegacy(callback);
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
        await loadScript(this.getScriptOptions());

        SwagPayPalScriptBase.paypal[this.scriptOptionsHash] = window.paypal;

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
        } else if (Array.isArray(this.options.disabledAlternativePaymentMethods)) {
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

    /**
     * @deprecated tag:v10.0.0 - will be removed without replacement
     */
    callCallbacks() {
        this.constructor.scriptLoading.callbacks.forEach((callback) => {
            SwagPayPalScriptBase.scriptPromises[this.scriptOptionsHash]
                .then((paypal) => callback.call(this, paypal));
        });
    }

    /**
     * @deprecated tag:v10.0.0 - will be removed without replacement
     */
    _createScriptLegacy(callback) {
        this.constructor.scriptLoading.callbacks.push(callback);
    }
}
