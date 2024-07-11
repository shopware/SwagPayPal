const ApiService = Shopware.Classes.ApiService;

export default class SwagPayPalWebhookService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'paypal') {
        super(httpClient, loginService, apiEndpoint);
    }

    register(salesChannelId) {
        return this.httpClient.post(
            `_action/${this.getApiBasePath()}/webhook/register/${salesChannelId}`,
            {},
            { headers: this.getBasicHeaders() },
        ).then(ApiService.handleResponse.bind(this));
    }

    unregister(salesChannelId) {
        return this.httpClient.delete(
            `_action/${this.getApiBasePath()}/webhook/register/${salesChannelId}`,
            {},
            { headers: this.getBasicHeaders() },
        ).then(ApiService.handleResponse.bind(this));
    }

    status(salesChannelId) {
        return this.httpClient.get(
            `_action/${this.getApiBasePath()}/webhook/status/${salesChannelId}`,
            {},
            { headers: this.getBasicHeaders() },
        ).then(ApiService.handleResponse.bind(this));
    }
}
