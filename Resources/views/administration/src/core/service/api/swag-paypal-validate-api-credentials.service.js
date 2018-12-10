import ApiService from 'src/core/service/api/api.service';

class SwagPayPalValidateApiCredentialsService extends ApiService {
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
                    headers: headers
                }
            )
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }
}

export default SwagPayPalValidateApiCredentialsService;
