const ApiService = Shopware.Classes.ApiService;

class SwagPayPalApiCredentialsService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'paypal') {
        super(httpClient, loginService, apiEndpoint);
    }

    validateApiCredentials(clientId, clientSecret, sandboxActive) {
        const headers = this.getBasicHeaders();

        return this.httpClient
            .get(
                `_action/${this.getApiBasePath()}/validate-api-credentials`,
                {
                    params: { clientId, clientSecret, sandboxActive },
                    headers: headers,
                },
            )
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }

    getApiCredentials(authCode, sharedId, nonce, sandboxActive, additionalParams = {}, additionalHeaders = {}) {
        const params = additionalParams;
        const headers = this.getBasicHeaders(additionalHeaders);

        return this.httpClient
            .post(`_action/${this.getApiBasePath()}/get-api-credentials`,
                { authCode, sharedId, nonce, sandboxActive },
                { params, headers })
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }

    /**
     * @param {string=} salesChannelId
     */
    getMerchantIntegrations(salesChannelId = null) {
        const headers = this.getBasicHeaders();

        return this.httpClient
            .get(
                `_action/${this.getApiBasePath()}/get-merchant-integrations`,
                {
                    params: { salesChannelId },
                    headers: headers,
                },
            )
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }
}

export default SwagPayPalApiCredentialsService;
