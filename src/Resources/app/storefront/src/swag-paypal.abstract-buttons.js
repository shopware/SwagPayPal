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
    static options = {
        /**
         * URL for adding flash error message
         *
         * @deprecated tag:v10.0.0 - Will be removed, use {@link handleErrorUrl} instead
         *
         * @type string
         */
        addErrorUrl: '',

        /**
         * URL for adding flash error message
         *
         * @type string
         */
        handleErrorUrl: '',
    }

    GENERIC_ERROR = 'SWAG_PAYPAL__GENERIC_ERROR';
    NOT_ELIGIBLE = 'SWAG_PAYPAL__NOT_ELIGIBLE';
    USER_CANCELLED = 'SWAG_PAYPAL__USER_CANCELLED';
    BROWSER_UNSUPPORTED = 'SWAG_PAYPAL__BROWSER_UNSUPPORTED';

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
     * @param {String} code - The error code. Will be replaced by an extracted error code from {@link error} if available
     * @param {Boolean} [fatal=false] - A fatal error will not allow a rerender of the PayPal buttons
     * @param {*} [error=undefined] - The error. Can be any type, but will be converted to a string
     */
    handleError(code, fatal = false, error = undefined) {
        if (error && typeof error !== 'string') {
            error = String(error);
        }

        const errorCode = this._extractErrorCode(error);
        if (errorCode) {
            code = errorCode;
        }

        if (!this.options.handleErrorUrl) {
            console.error(`PayPal ${fatal ? 'fatal ' : ''}error occurred: ${code} - ${String(error ?? '')}`);
            return;
        }

        this._client.post(this.options.handleErrorUrl, JSON.stringify({
            code,
            error,
            fatal,
        }), () => {
            window.onbeforeunload = () => { window.scrollTo(0, 0); };
            window.location.reload();
        });
    }

    /**
     * Stop payment process with a generic __fatal__ error.
     * Will prevent rendering the button through the render function.
     *
     * @param {*} [error=undefined] - Can be any type, but will be converted to a string
     */
    onFatalError(error = undefined) {
        this.handleError(this.GENERIC_ERROR, true, error);
    }

    /**
     * Stop payment process with a generic error.
     * Will __NOT__ prevent rendering the button through the render function.
     *
     * @param {*} [error=undefined] - Can be any type, but will be converted to a string
     */
    onError(error = undefined) {
        this.handleError(this.GENERIC_ERROR, false, error);
    }

    /**
     * Cancel the payment process with a generic cancellation.
     * Will __NOT__ prevent rendering the button through the render function.
     *
     * @param {*} [error=undefined] - Can be any type, but will be converted to a string
     */
    onCancel(error = undefined) {
        this.handleError(this.USER_CANCELLED, false, error);
    }

    /**
     * @deprecated tag:v10.0.0 - Will be removed, use {@link handleError} instead
     *
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

        if (!!error && typeof error !== 'string') {
            error = String(error);
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

    /**
     * @private
     * @returns {String|null}
     */
    _extractErrorCode(error) {
        try {
            const errors = JSON.parse(error)?.errors;

            if (!Array.isArray(errors)) {
                return null;
            }

            for (const error of errors) {
                if (typeof error !== 'object') {
                    continue;
                }

                if (typeof error.code === 'string') {
                    return error.code;
                }
            }
        } catch { /* no error handling needed */ }

        return null;
    }
}
