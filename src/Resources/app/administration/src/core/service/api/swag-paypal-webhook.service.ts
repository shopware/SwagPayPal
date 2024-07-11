import type { LoginService } from 'src/core/service/login.service';
import type { AxiosInstance } from 'axios';
import type * as PayPal from 'src/types';

const ApiService = Shopware.Classes.ApiService;

export default class SwagPayPalWebhookService extends ApiService {
    constructor(httpClient: AxiosInstance, loginService: LoginService, apiEndpoint = 'paypal') {
        super(httpClient, loginService, apiEndpoint);
    }

    register(salesChannelId: string | null) {
        return this.httpClient.post<PayPal.Api.Operations<'registerWebhook'>>(
            `_action/${this.getApiBasePath()}/webhook/register/${salesChannelId}`,
            {},
            { headers: this.getBasicHeaders() },
        ).then(ApiService.handleResponse.bind(this));
    }

    unregister(salesChannelId: string | null) {
        return this.httpClient.delete<PayPal.Api.Operations<'deregisterWebhook'>>(
            `_action/${this.getApiBasePath()}/webhook/register/${salesChannelId}`,
            { headers: this.getBasicHeaders() },
        ).then(ApiService.handleResponse.bind(this));
    }

    status(salesChannelId: string | null) {
        return this.httpClient.get<PayPal.Api.Operations<'getWebhookStatus'>>(
            `_action/${this.getApiBasePath()}/webhook/status/${salesChannelId}`,
            { headers: this.getBasicHeaders() },
        ).then(ApiService.handleResponse.bind(this));
    }
}
