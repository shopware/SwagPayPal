import Plugin from 'src/plugin-system/plugin.class';
import HttpClient from 'src/service/http-client.service';
import LoadingIndicator from 'src/utility/loading-indicator/loading-indicator.util';

export default class SwagPaypalPuiPolling extends Plugin {
    static options = {
        /**
         * @type string
         */
        pollingUrl: '',

        /**
         * @type string
         */
        successUrl: '',

        /**
         * @type string
         */
        errorUrl: '',

        /**
         * @type object
         */
        paymentInstructions: null,

        /**
         * @type int
         */
        pollingInterval: 2000,
    };

    init() {
        (new LoadingIndicator(this.el)).create();
        this._client = new HttpClient();

        this.poll();
    }

    poll() {
        this._client.get(this.options.pollingUrl, this.onPollingResult.bind(this));
    }

    onPollingResult(responseText, request) {
        // not ready
        if (request.status === 417) {
            this.retryPolling();
            return;
        }

        // payment instructions could not be fetched, payment failed
        if (request.status >= 400) {
            window.location = this.options.errorUrl;
            return;
        }

        // payment instructions can be fetched, reload for timing
        window.location = this.options.successUrl;
    }

    retryPolling() {
        setTimeout(this.poll.bind(this), this.options.pollingInterval);
    }
}
