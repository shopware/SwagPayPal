import type { LoginService } from 'src/core/service/login.service';
import type { AxiosInstance } from 'axios';
import type * as PayPal from 'SwagPayPal/types';

const ApiService = Shopware.Classes.ApiService;

export default class SwagPayPalSettingsService extends ApiService {
    constructor(httpClient: AxiosInstance, loginService: LoginService, apiEndpoint = 'paypal') {
        super(httpClient, loginService, apiEndpoint);
    }

    save(allConfigs: Record<string, PayPal.SystemConfig>) {
        return this.httpClient.post<PayPal.Api.Operations<'saveSettings'>>(
            `_action/${this.getApiBasePath()}/save-settings`,
            allConfigs,
            { headers: this.getBasicHeaders() },
        ).then(ApiService.handleResponse.bind(this));
    }

    testApiCredentials(clientId?: string, clientSecret?: string, merchantPayerId?: string, sandboxActive?: boolean) {
        return this.httpClient.post<PayPal.Api.Operations<'testApiCredentials'>>(
            `_action/${this.getApiBasePath()}/test-api-credentials`,
            { clientId, clientSecret, sandboxActive, merchantPayerId },
            { headers: this.getBasicHeaders() },
        ).then(ApiService.handleResponse.bind(this));
    }

    getApiCredentials(
        authCode: string,
        sharedId: string,
        nonce: string,
        sandboxActive: boolean,
        params: object = {},
        additionalHeaders: object = {},
    ) {
        return this.httpClient.post<PayPal.Api.Operations<'getApiCredentials'>>(
            `_action/${this.getApiBasePath()}/get-api-credentials`,
            { authCode, sharedId, nonce, sandboxActive },
            { params, headers: this.getBasicHeaders(additionalHeaders) },
        ).then(ApiService.handleResponse.bind(this));
    }

    getMerchantInformation(salesChannelId: string | null = null) {
        return this.httpClient.get<PayPal.Api.Operations<'getMerchantInformation'>>(
            `_action/${this.getApiBasePath()}/merchant-information`,
            {
                params: { salesChannelId },
                headers: this.getBasicHeaders(),
            },
        ).then(ApiService.handleResponse.bind(this));
    }
}
