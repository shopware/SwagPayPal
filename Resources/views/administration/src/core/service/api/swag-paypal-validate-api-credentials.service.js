import ApiService from 'src/core/service/api/api.service';

class SwagPayPalValidateApiCredentialsService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'paypal') {
        super(httpClient, loginService, apiEndpoint);
    }

    validateApiCredentials(clientId, clientSecret, sandboxActive) {
        const headers = this.getBasicHeaders();

        return this.httpClient
            .post(
                `_action/${this.getApiBasePath()}/validate-api-credentials`,
                { clientId, clientSecret, sandboxActive },
                { headers }
            )
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }
}

export default SwagPayPalValidateApiCredentialsService;
