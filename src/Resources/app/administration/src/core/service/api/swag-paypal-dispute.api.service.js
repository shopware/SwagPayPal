const ApiService = Shopware.Classes.ApiService;

class SwagPayPalDisputeApiService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'paypal/dispute') {
        super(httpClient, loginService, apiEndpoint);
    }

    /**
     * Get a list of all disputes.
     * Provide a sales channel ID if you have different merchant accounts for your sales channels.
     * Disputes could also be filtered by their state.
     *
     * @param {String|null} salesChannelId
     * @param {String|null} disputeStateFilter
     *
     * @returns {Promise}
     */
    list(salesChannelId = null, disputeStateFilter = null) {
        const headers = this.getBasicHeaders();

        return this.httpClient.get(
            this.getApiBasePath(),
            {
                params: { salesChannelId, disputeStateFilter },
                headers,
                version: Shopware.Context.api.apiVersion
            }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }
}

export default SwagPayPalDisputeApiService;
