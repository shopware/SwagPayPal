/* eslint-disable import/no-unresolved */

import Plugin from 'src/plugin-system/plugin.class';

let loadingScript = false;
let scriptLoaded = false;
const callbacks = [];

export default class SwagPaypalAbstractButtons extends Plugin {
    createScript(callback) {
        callbacks.push(callback);

        if (loadingScript) {
            if (scriptLoaded) {
                callback.call(this);
            }
            return;
        }

        loadingScript = true;
        const scriptOptions = this.getScriptUrlOptions();
        const payPalScriptUrl = `https://www.paypal.com/sdk/js?client-id=${this.options.clientId}${scriptOptions}`;
        const payPalScript = document.createElement('script');
        payPalScript.type = 'text/javascript';
        payPalScript.src = payPalScriptUrl;

        payPalScript.addEventListener('load', this.callCallbacks.bind(this), false);
        document.head.appendChild(payPalScript);
    }

    callCallbacks() {
        callbacks.forEach(callback => {
            callback.call(this);
        });

        scriptLoaded = true;
    }

    /**
     * @return {string}
     */
    getScriptUrlOptions() {
        let config = '';

        if (typeof this.options.commit !== 'undefined') {
            config += `&commit=${this.options.commit}`;
        }

        if (this.options.languageIso) {
            config += `&locale=${this.options.languageIso}`;
        }

        if (this.options.currency) {
            config += `&currency=${this.options.currency}`;
        }

        if (this.options.intent && this.options.intent !== 'sale') {
            config += `&intent=${this.options.intent}`;
        }

        if (this.options.useAlternativePaymentMethods !== undefined && !this.options.useAlternativePaymentMethods) {
            config += '&disable-funding=credit,card,sepa,bancontact,eps,giropay,ideal,mybank,sofort';
        }

        config += '&components=marks,buttons,messages';

        return config;
    }
}
