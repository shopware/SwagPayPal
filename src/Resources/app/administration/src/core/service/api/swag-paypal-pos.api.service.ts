const ApiService = Shopware.Classes.ApiService;

class SwagPayPalPosApiService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'paypal/pos') {
        super(httpClient, loginService, apiEndpoint);
        this.basicConfig = {
            timeout: 300000,
        };
    }

    startCompleteSync(salesChannelId) {
        return this.httpClient.post(
            `_action/${this.getApiBasePath()}/sync/${salesChannelId}`,
            null,
            {
                ...this.basicConfig,
                headers: this.getBasicHeaders(),
            },
        ).then(ApiService.handleResponse.bind(this));
    }

    startProductSync(salesChannelId) {
        return this.httpClient.post(
            `_action/${this.getApiBasePath()}/sync/${salesChannelId}/products`,
            null,
            {
                ...this.basicConfig,
                headers: this.getBasicHeaders(),
            },
        ).then(ApiService.handleResponse.bind(this));
    }

    startInventorySync(salesChannelId) {
        return this.httpClient.post(
            `_action/${this.getApiBasePath()}/sync/${salesChannelId}/inventory`,
            null,
            {
                ...this.basicConfig,
                headers: this.getBasicHeaders(),
            },
        ).then(ApiService.handleResponse.bind(this));
    }

    startImageSync(salesChannelId) {
        return this.httpClient.post(
            `_action/${this.getApiBasePath()}/sync/${salesChannelId}/images`,
            null,
            {
                ...this.basicConfig,
                headers: this.getBasicHeaders(),
            },
        ).then(ApiService.handleResponse.bind(this));
    }

    startLogCleanup(salesChannelId) {
        return this.httpClient.post(
            `_action/${this.getApiBasePath()}/log/cleanup/${salesChannelId}`,
            null,
            {
                ...this.basicConfig,
                headers: this.getBasicHeaders(),
            },
        ).then(ApiService.handleResponse.bind(this));
    }

    abortSync(runId) {
        return this.httpClient.post(
            `_action/${this.getApiBasePath()}/sync/abort/${runId}`,
            null,
            {
                ...this.basicConfig,
                headers: this.getBasicHeaders(),
            },
        ).then(ApiService.handleResponse.bind(this));
    }

    resetSync(salesChannelId) {
        return this.httpClient.post(
            `_action/${this.getApiBasePath()}/sync/reset/${salesChannelId}`,
            null,
            {
                ...this.basicConfig,
                headers: this.getBasicHeaders(),
            },
        ).then(ApiService.handleResponse.bind(this));
    }

    getProductLog(salesChannelId, page = 1, limit = 10) {
        return this.httpClient.get(
            `${this.getApiBasePath()}/product-log/${salesChannelId}`,
            {
                ...this.basicConfig,
                headers: this.getBasicHeaders(),
                params: { page, limit },
            },
        ).then(ApiService.handleResponse.bind(this));
    }
}

export default SwagPayPalPosApiService;
