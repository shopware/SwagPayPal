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
        const apiRoute = `_action/${this.getApiBasePath()}/saleschannel-default`;

        return this.httpClient.post(
            apiRoute,
            {
                salesChannelId,
            },
            {
                headers: this.getBasicHeaders(),
            },
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }
}

export default SwagPaypalPaymentMethodService;
