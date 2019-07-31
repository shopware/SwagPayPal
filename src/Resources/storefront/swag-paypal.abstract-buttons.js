/* eslint-disable import/no-unresolved */

import Plugin from 'src/script/plugin-system/plugin.class';

export default class SwagPaypalAbstractButtons extends Plugin {
    createScript(callback) {
        const scriptOptions = this.getScriptUrlOptions();
        const payPalScriptUrl = `https://www.paypal.com/sdk/js?client-id=${this.options.clientId}${scriptOptions}`;
        const payPalScript = document.createElement('script');
        payPalScript.type = 'text/javascript';
        payPalScript.src = payPalScriptUrl;

        payPalScript.addEventListener('load', callback.bind(this), false);
        document.head.appendChild(payPalScript);

        return payPalScript;
    }

    /**
     * @return {string}
     */
    getScriptUrlOptions() {
        let config = '';
        config += `&locale=${this.options.languageIso}`;
        config += `&commit=${this.options.commit}`;

        if (this.options.currency) {
            config += `&currency=${this.options.currency}`;
        }

        if (this.options.intent && this.options.intent !== 'sale') {
            config += `&intent=${this.options.intent}`;
        }

        return config;
    }
}
