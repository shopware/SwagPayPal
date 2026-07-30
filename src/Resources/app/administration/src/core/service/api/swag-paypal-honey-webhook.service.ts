import type { LoginService } from 'src/core/service/login.service';
import type { AxiosInstance } from 'axios';
import type * as PayPal from 'SwagPayPal/types';

const ApiService = Shopware.Classes.ApiService;

export default class SwagPayPalHoneyWebhookService extends ApiService {
    constructor(httpClient: AxiosInstance, loginService: LoginService, apiEndpoint = 'paypal') {
        super(httpClient, loginService, apiEndpoint);
    }

    register(salesChannelId: string | null) {
        return this.httpClient.post<PayPal.Api.Operations<'registerHoneyWebhook'>>(
            `_action/${this.getApiBasePath()}/honey/webhook/register/${salesChannelId}`,
            {},
            { headers: this.getBasicHeaders() },
        ).then(ApiService.handleResponse.bind(this));
    }
}
