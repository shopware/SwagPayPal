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
        return this.httpClient.get(
            this.getApiBasePath(),
            {
                params: { salesChannelId, disputeStateFilter },
                headers: this.getBasicHeaders(),
            },
        ).then(ApiService.handleResponse.bind(this));
    }

    /**
     * Get the details of a dispute
     *
     * @param {String} disputeId
     * @param {String|null} salesChannelId
     *
     * @returns {Promise}
     */
    detail(disputeId, salesChannelId) {
        return this.httpClient.get(
            `${this.getApiBasePath()}/${disputeId}`,
            {
                params: { salesChannelId },
                headers: this.getBasicHeaders(),
            },
        ).then(ApiService.handleResponse.bind(this));
    }
}

export default SwagPayPalDisputeApiService;
