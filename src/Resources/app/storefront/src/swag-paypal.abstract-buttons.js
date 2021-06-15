/* eslint-disable import/no-unresolved */

import Plugin from 'src/plugin-system/plugin.class';
import DomAccess from 'src/helper/dom-access.helper';
import SwagPayPalScriptLoading from './swag-paypal.script-loading';

const availableAPMs = [
    'card',
    'credit',
    'bancontact',
    'blik',
    'eps',
    'giropay',
    'ideal',
    'mybank',
    'p24',
    'sepa',
    'sofort',
    'venmo'
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
        const scriptOptions = this.getScriptUrlOptions();
        const payPalScriptUrl = `https://www.paypal.com/sdk/js?client-id=${this.options.clientId}${scriptOptions}`;
        const payPalScript = document.createElement('script');
        payPalScript.type = 'text/javascript';
        payPalScript.src = payPalScriptUrl;

        payPalScript.addEventListener('load', this.callCallbacks.bind(this), false);
        document.head.appendChild(payPalScript);
    }

    callCallbacks() {
        if (this.constructor.scriptLoading.paypal === null) {
            this.constructor.scriptLoading.paypal = window.paypal;
            delete window.paypal;
        }

        this.constructor.scriptLoading.callbacks.forEach(callback => {
            callback.call(this, this.constructor.scriptLoading.paypal);
        });
    }

    /**
     * @return {string}
     */
    getScriptUrlOptions() {
        let config = '&components=marks,buttons,messages';

        if (typeof this.options.commit !== 'undefined') {
            config += `&commit=${this.options.commit}`;
        }

        if (this.options.languageIso) {
            config += `&locale=${this.options.languageIso}`;
        }

        if (this.options.currency) {
            config += `&currency=${this.options.currency}`;
        }

        if (this.options.intent) {
            config += `&intent=${this.options.intent}`;
        }

        if (this.options.useAlternativePaymentMethods !== undefined && !this.options.useAlternativePaymentMethods) {
            config += `&disable-funding=${availableAPMs.join(',')}`;
        } else if (this.options.disabledAlternativePaymentMethods !== undefined
                && this.options.disabledAlternativePaymentMethods.length > 0
        ) {
            config += `&disable-funding=${this.options.disabledAlternativePaymentMethods.join(',')}`;
        }

        return config;
    }

    createError(error) {
        if (process.env.NODE_ENV !== 'production' && typeof console !== 'undefined' && typeof this._client === 'undefined') {
            console.error('No StoreApiClient defined in child plugin class');
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

        const requestPayload = {
            _csrf_token: DomAccess.getDataAttribute(this.el, 'data-swag-pay-pal-add-error-token'),
            error: error
        };

        this._client.post(addErrorUrl, JSON.stringify(requestPayload), () => {
            window.onbeforeunload = () => {
                window.scrollTo(0, 0);
            };
            window.location.reload();
        });
    }
}
