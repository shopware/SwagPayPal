const ApiService = Shopware.Classes.ApiService;

class SwagPayPalPosWebhookRegisterService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'paypal/pos') {
        super(httpClient, loginService, apiEndpoint);
    }

    registerWebhook(salesChannelId) {
        return this.httpClient.post(
            `_action/${this.getApiBasePath()}/webhook/registration/${salesChannelId}`,
            {},
            { headers: this.getBasicHeaders() },
        ).then(ApiService.handleResponse.bind(this));
    }

    unregisterWebhook(salesChannelId) {
        return this.httpClient.delete(
            `_action/${this.getApiBasePath()}/webhook/registration/${salesChannelId}`,
            {},
            { headers: this.getBasicHeaders() },
        ).then(ApiService.handleResponse.bind(this));
    }
}

export default SwagPayPalPosWebhookRegisterService;
