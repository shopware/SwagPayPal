const ApiService = Shopware.Classes.ApiService;

class SwagPaypalPaymentMethodService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'paypal') {
        super(httpClient, loginService, apiEndpoint);
    }

    /**
     * Sets the default payment method to PayPal for the given Sales Channel id.
     *
     * @param {String|null} salesChannelId
     *
     * @returns {Promise}
     */
    setDefaultPaymentForSalesChannel(salesChannelId = null) {
        return this.httpClient.post(
            `_action/${this.getApiBasePath()}/saleschannel-default`,
            { salesChannelId },
            { headers: this.getBasicHeaders() },
        ).then(ApiService.handleResponse.bind(this));
    }
}

export default SwagPaypalPaymentMethodService;
