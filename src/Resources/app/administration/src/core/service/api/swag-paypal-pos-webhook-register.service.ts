import type * as PayPal from 'src/types';
import type { LoginService } from 'src/core/service/login.service';
import type { AxiosInstance } from 'axios';

const ApiService = Shopware.Classes.ApiService;

class SwagPayPalPosWebhookRegisterService extends ApiService {
    constructor(httpClient: AxiosInstance, loginService: LoginService, apiEndpoint = 'paypal/pos') {
        super(httpClient, loginService, apiEndpoint);
    }

    registerWebhook(salesChannelId: string) {
        return this.httpClient.post<PayPal.Api.Operations<'registerPosWebhook'>>(
            `_action/${this.getApiBasePath()}/webhook/registration/${salesChannelId}`,
            {},
            { headers: this.getBasicHeaders() },
        ).then(ApiService.handleResponse.bind(this));
    }

    unregisterWebhook(salesChannelId: string) {
        return this.httpClient.delete<PayPal.Api.Operations<'deregisterPosWebhook'>>(
            `_action/${this.getApiBasePath()}/webhook/registration/${salesChannelId}`,
            { headers: this.getBasicHeaders() },
        ).then(ApiService.handleResponse.bind(this));
    }
}

export default SwagPayPalPosWebhookRegisterService;
