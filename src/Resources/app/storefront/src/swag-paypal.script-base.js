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
         * @type string
         */
        pageType: 'checkout',

        /**
         * This option will await the visibility of the element before continue loading the script.
         * Useful for listing pages to not load all express buttons at once.
         *
         * @type boolean
         */
        scriptAwaitVisibility: false,
    };

    static scriptPromises = {};

    static paypal = {};

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
        const scriptTag = document.getElementById('paypal-sdk-v6');

        if (!scriptTag) {
            throw new Error('PayPal script is not present');
        }

        if (!window.paypal) {
            await new Promise((resolve) => scriptTag.addEventListener('load', resolve));
        }

        return await window.paypal.createInstance(this.getScriptOptions());
    }

    /**
     * The options the PayPal script will be loaded with.
     * Make sure to not create a flaky order of options, as this will
     * mess up the `scriptOptionsHash` and therefore affects script caching.
     */
    getScriptOptions() {
        return {
            clientToken: this.options.clientToken,
            components: ['paypal-payments'],
            locale: this.options.languageIso,

            pageType: this.options.pageType,
        };

        // if (this.options.disablePayLater || this.options.showPayLater === false) {
        //     config['enable-funding'] = 'venmo';
        // }

        // if (this.options.useAlternativePaymentMethods === false) {
        //     config['disable-funding'] = availableAPMs.join(',');
        // } else if (this.options.disabledAlternativePaymentMethods.length > 0) {
        //     config['disable-funding'] = this.options.disabledAlternativePaymentMethods.join(',');
        // }

        // if (this.options.merchantPayerId) {
        //     config['merchant-id'] = this.options.merchantPayerId;
        // }

        // if (this.options.clientToken) {
        //     config['data-client-token'] = this.options.clientToken;
        // }

        // if (this.options.userIdToken) {
        //     config['data-user-id-token'] = this.options.userIdToken;
        // }

        // if (this.options.partnerAttributionId) {
        //     config['data-partner-attribution-id'] = this.options.partnerAttributionId;
        // }
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
