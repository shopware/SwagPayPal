import type * as PayPal from 'src/types';
import type { LoginService } from 'src/core/service/login.service';
import type { AxiosInstance } from 'axios';

const ApiService = Shopware.Classes.ApiService;

class SwagPayPalPosApiService extends ApiService {
    basicConfig: { timeout: number };

    constructor(httpClient: AxiosInstance, loginService: LoginService, apiEndpoint = 'paypal/pos') {
        super(httpClient, loginService, apiEndpoint);
        this.basicConfig = {
            timeout: 300000,
        };
    }

    startCompleteSync(salesChannelId: string | null) {
        return this.httpClient.post<PayPal.Api.Operations<'posSync'>>(
            `_action/${this.getApiBasePath()}/sync/${salesChannelId}`,
            null,
            {
                ...this.basicConfig,
                headers: this.getBasicHeaders(),
            },
        ).then(ApiService.handleResponse.bind(this) as TResponseHandler);
    }

    startProductSync(salesChannelId: string | null) {
        return this.httpClient.post<PayPal.Api.Operations<'posSyncProducts'>>(
            `_action/${this.getApiBasePath()}/sync/${salesChannelId}/products`,
            null,
            {
                ...this.basicConfig,
                headers: this.getBasicHeaders(),
            },
        ).then(ApiService.handleResponse.bind(this) as TResponseHandler);
    }

    startInventorySync(salesChannelId: string | null) {
        return this.httpClient.post<PayPal.Api.Operations<'posSyncInventory'>>(
            `_action/${this.getApiBasePath()}/sync/${salesChannelId}/inventory`,
            null,
            {
                ...this.basicConfig,
                headers: this.getBasicHeaders(),
            },
        ).then(ApiService.handleResponse.bind(this) as TResponseHandler);
    }

    startImageSync(salesChannelId: string | null) {
        return this.httpClient.post<PayPal.Api.Operations<'posSyncImages'>>(
            `_action/${this.getApiBasePath()}/sync/${salesChannelId}/images`,
            null,
            {
                ...this.basicConfig,
                headers: this.getBasicHeaders(),
            },
        ).then(ApiService.handleResponse.bind(this) as TResponseHandler);
    }

    startLogCleanup(salesChannelId: string | null) {
        return this.httpClient.post<PayPal.Api.Operations<'posProductLog'>>(
            `_action/${this.getApiBasePath()}/log/cleanup/${salesChannelId}`,
            null,
            {
                ...this.basicConfig,
                headers: this.getBasicHeaders(),
            },
        ).then(ApiService.handleResponse.bind(this) as TResponseHandler);
    }

    abortSync(runId: string) {
        return this.httpClient.post<PayPal.Api.Operations<'posSyncAbort'>>(
            `_action/${this.getApiBasePath()}/sync/abort/${runId}`,
            null,
            {
                ...this.basicConfig,
                headers: this.getBasicHeaders(),
            },
        ).then(ApiService.handleResponse.bind(this) as TResponseHandler);
    }

    resetSync(salesChannelId: string | null) {
        return this.httpClient.post<PayPal.Api.Operations<'posSyncReset'>>(
            `_action/${this.getApiBasePath()}/sync/reset/${salesChannelId}`,
            null,
            {
                ...this.basicConfig,
                headers: this.getBasicHeaders(),
            },
        ).then(ApiService.handleResponse.bind(this) as TResponseHandler);
    }

    getProductLog(salesChannelId: string | null, page = 1, limit = 10) {
        return this.httpClient.get<PayPal.Api.Operations<'posProductLog'>>(
            `${this.getApiBasePath()}/product-log/${salesChannelId}`,
            {
                ...this.basicConfig,
                headers: this.getBasicHeaders(),
                params: { page, limit },
            },
        ).then(ApiService.handleResponse.bind(this) as TResponseHandler);
    }
}

export default SwagPayPalPosApiService;
