import type { LoginService } from 'src/core/service/login.service';
import type { AxiosInstance } from 'axios';
import type * as PayPal from 'SwagPayPal/types';

const ApiService = Shopware.Classes.ApiService;

export default class SwagPayPalDiagnosticsService extends ApiService {
    constructor(httpClient: AxiosInstance, loginService: LoginService, apiEndpoint = 'paypal') {
        super(httpClient, loginService, apiEndpoint);
    }

    twig(salesChannelId: string): Promise<PayPal.Api.Operations<'getPayPalTwigDiagnostics'>> {
        return this.httpClient.get<PayPal.Api.Operations<'getPayPalTwigDiagnostics'>>(
            `_action/${this.getApiBasePath()}/diagnostics/twig/${salesChannelId}`,
            { headers: this.getBasicHeaders() },
        ).then(ApiService.handleResponse.bind(this));
    }
}
