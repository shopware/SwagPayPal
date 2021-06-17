const ApiService = Shopware.Classes.ApiService;

class SwagPaypalPaymentMethodServiceService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'paypal') {
        super(httpClient, loginService, apiEndpoint);
    }

    /**
     * Set's the default payment method to PayPal for the given Saleschannel id.
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

export default SwagPaypalPaymentMethodServiceService;
