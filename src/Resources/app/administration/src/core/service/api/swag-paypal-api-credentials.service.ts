import type { LoginService } from 'src/core/service/login.service';
import type { AxiosInstance } from 'axios';
import type * as PayPal from 'src/types';

const ApiService = Shopware.Classes.ApiService;

class SwagPayPalApiCredentialsService extends ApiService {
    constructor(httpClient: AxiosInstance, loginService: LoginService, apiEndpoint = 'paypal') {
        super(httpClient, loginService, apiEndpoint);
    }

    validateApiCredentials(clientId: string | undefined, clientSecret: string | undefined, sandboxActive: boolean, merchantPayerId: string | null = null) {
        return this.httpClient.get<PayPal.Api.Operations<'validateApiCredentials'>>(
            `_action/${this.getApiBasePath()}/validate-api-credentials`,
            {
                params: { clientId, clientSecret, sandboxActive, merchantPayerId },
                headers: this.getBasicHeaders(),
            },
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

export default SwagPayPalApiCredentialsService;
