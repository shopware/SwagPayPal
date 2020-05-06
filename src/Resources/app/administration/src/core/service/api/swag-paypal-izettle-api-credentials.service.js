const ApiService = Shopware.Classes.ApiService;

class SwagPayPalIZettleApiCredentialsService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'paypal/izettle') {
        super(httpClient, loginService, apiEndpoint);
    }

    /**
     * Checks, if an access token for this user data can be created
     *
     * @param username
     * @param password
     * @returns {Promise|Object}
     */
    validateApiCredentials(username, password) {
        const headers = this.getBasicHeaders();

        return this.httpClient
            .get(
                `_action/${this.getApiBasePath()}/validate-api-credentials`,
                {
                    params: { username, password },
                    headers: headers
                }
            )
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }
}

export default SwagPayPalIZettleApiCredentialsService;
