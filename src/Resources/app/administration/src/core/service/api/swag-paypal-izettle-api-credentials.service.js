const ApiService = Shopware.Classes.ApiService;

class SwagPayPalIZettleApiCredentialsService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'paypal/izettle') {
        super(httpClient, loginService, apiEndpoint);
    }

    /**
     * Checks, if an access token for this user data can be created
     *
     * @param apiKey
     * @returns {Promise|Object}
     */
    validateApiCredentials(apiKey) {
        const headers = this.getBasicHeaders();

        return this.httpClient.post(
            `_action/${this.getApiBasePath()}/validate-api-credentials`,
            { apiKey },
            { headers }
        )
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }

    generateApiUrl() {
        const scopes = [
            'READ:PURCHASE',
            'READ:FINANCE',
            'READ:USERINFO',
            'READ:PRODUCT',
            'WRITE:PRODUCT'
        ];

        return `https://my.izettle.com/apps/api-keys?name=Shopware%20integration&scopes=${scopes.join('%20')}`;
    }
}

export default SwagPayPalIZettleApiCredentialsService;
