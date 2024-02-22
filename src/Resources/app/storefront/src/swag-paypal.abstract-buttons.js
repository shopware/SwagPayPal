import Plugin from 'src/plugin-system/plugin.class';
import {loadScript} from '@paypal/paypal-js';
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

export default class SwagPaypalAbstractButtons extends Plugin {
    static scriptLoading = new SwagPayPalScriptLoading();

    createScript(callback) {
        if (this.constructor.scriptLoading.paypal !== null) {
            callback.call(this, this.constructor.scriptLoading.paypal);
            return;
        }

        this.constructor.scriptLoading.callbacks.push(callback);

        if (this.constructor.scriptLoading.loadingScript) {
            return;
        }

        this.constructor.scriptLoading.loadingScript = true;

        loadScript(this.getScriptOptions()).then(this.callCallbacks.bind(this));
    }

    callCallbacks() {
        if (this.constructor.scriptLoading.paypal === null) {
            this.constructor.scriptLoading.paypal = window.paypal;
            delete window.paypal;
        }

        this.constructor.scriptLoading.callbacks.forEach((callback) => {
            callback.call(this, this.constructor.scriptLoading.paypal);
        });
    }

    /**
     * @return {Object}
     */
    getScriptOptions() {
        const config = {
            components: 'buttons,messages,card-fields,funding-eligibility',
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
     * @param {'cancel'|'browser'|'error'} type
     * @param {*=} error
     * @param {String=} redirect
     * @returns {void}
     */
    createError(type, error = undefined, redirect = '') {
        if (process.env.NODE_ENV !== 'production' && typeof console !== 'undefined' && typeof this._client === 'undefined') {
            console.error('No HttpClient defined in child plugin class');
            return;
        }

        const addErrorUrl = this.options.addErrorUrl;
        if (process.env.NODE_ENV !== 'production'
            && typeof console !== 'undefined'
            && (typeof addErrorUrl === 'undefined' || addErrorUrl === null)
        ) {
            console.error('No "addErrorUrl" defined in child plugin class');
            return;
        }

        if (this.options.accountOrderEditCancelledUrl && this.options.accountOrderEditFailedUrl) {
            window.location = type === 'cancel' ? this.options.accountOrderEditCancelledUrl : this.options.accountOrderEditFailedUrl;

            return;
        }

        this._client.post(addErrorUrl, JSON.stringify({error, type}), () => {
            if (redirect) {
                window.location = redirect;
                return;
            }

            window.onbeforeunload = () => {
                window.scrollTo(0, 0);
            };
            window.location.reload();
        });
    }
}
