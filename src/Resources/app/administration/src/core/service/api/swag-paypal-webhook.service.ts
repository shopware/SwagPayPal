const ApiService = Shopware.Classes.ApiService;

export default class SwagPayPalWebhookService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'paypal') {
        super(httpClient, loginService, apiEndpoint);
    }

    register(salesChannelId) {
        const headers = this.getBasicHeaders();

        return this.httpClient
            .post(`_action/${this.getApiBasePath()}/webhook/register/${salesChannelId}`, {}, { headers })
            .then((response) => ApiService.handleResponse(response));
    }

    unregister(salesChannelId) {
        const headers = this.getBasicHeaders();

        return this.httpClient
            .delete(`_action/${this.getApiBasePath()}/webhook/register/${salesChannelId}`, {}, { headers })
            .then((response) => ApiService.handleResponse(response));
    }

    status(salesChannelId) {
        const headers = this.getBasicHeaders();

        return this.httpClient
            .get(`_action/${this.getApiBasePath()}/webhook/status/${salesChannelId}`, {}, { headers })
            .then((response) => ApiService.handleResponse(response));
    }
}
