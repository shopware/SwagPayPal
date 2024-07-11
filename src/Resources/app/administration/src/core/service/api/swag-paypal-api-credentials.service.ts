const ApiService = Shopware.Classes.ApiService;

class SwagPayPalApiCredentialsService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'paypal') {
        super(httpClient, loginService, apiEndpoint);
    }

    validateApiCredentials(clientId, clientSecret, sandboxActive) {
        return this.httpClient.get(
            `_action/${this.getApiBasePath()}/validate-api-credentials`,
            {
                params: { clientId, clientSecret, sandboxActive },
                headers: this.getBasicHeaders(),
            },
        ).then(ApiService.handleResponse.bind(this));
    }

    getApiCredentials(
        authCode,
        sharedId,
        nonce,
        sandboxActive,
        params = {},
        additionalHeaders = {},
    ) {
        return this.httpClient.post(
            `_action/${this.getApiBasePath()}/get-api-credentials`,
            { authCode, sharedId, nonce, sandboxActive },
            { params, headers: this.getBasicHeaders(additionalHeaders) },
        ).then(ApiService.handleResponse.bind(this));
    }

    /**
     * @param {string|null} salesChannelId
     */
    getMerchantInformation(salesChannelId = null) {
        return this.httpClient.get(
            `_action/${this.getApiBasePath()}/merchant-information`,
            {
                params: { salesChannelId },
                headers: this.getBasicHeaders(),
            },
        ).then(ApiService.handleResponse.bind(this));
    }
}

export default SwagPayPalApiCredentialsService;
