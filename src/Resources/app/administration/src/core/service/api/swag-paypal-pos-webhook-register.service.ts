const ApiService = Shopware.Classes.ApiService;

class SwagPayPalPosWebhookRegisterService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'paypal/pos') {
        super(httpClient, loginService, apiEndpoint);
    }

    registerWebhook(salesChannelId) {
        const headers = this.getBasicHeaders();

        return this.httpClient
            .post(`_action/${this.getApiBasePath()}/webhook/registration/${salesChannelId}`, {}, { headers })
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }

    unregisterWebhook(salesChannelId) {
        const headers = this.getBasicHeaders();

        return this.httpClient
            .delete(`_action/${this.getApiBasePath()}/webhook/registration/${salesChannelId}`, {}, { headers })
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }
}

export default SwagPayPalPosWebhookRegisterService;
