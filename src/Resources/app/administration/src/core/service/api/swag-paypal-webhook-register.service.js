const ApiService = Shopware.Classes.ApiService;

/**
 * @deprecated tag:v2.0.0 - will be removed
 */
class SwagPayPalWebhookRegisterService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'paypal') {
        super(httpClient, loginService, apiEndpoint);
    }

    registerWebhook(salesChannelId) {
        const headers = this.getBasicHeaders();

        return this.httpClient
            .post(`_action/${this.getApiBasePath()}/webhook/register/${salesChannelId}`, {}, {
                headers
            })
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }
}

export default SwagPayPalWebhookRegisterService;
