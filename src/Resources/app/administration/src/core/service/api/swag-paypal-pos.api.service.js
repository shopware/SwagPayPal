const ApiService = Shopware.Classes.ApiService;

class SwagPayPalPosApiService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'paypal/pos') {
        super(httpClient, loginService, apiEndpoint);
        this.basicConfig = {
            timeout: 300000
        };
    }

    startCompleteSync(salesChannelId) {
        const headers = this.getBasicHeaders();

        return this.httpClient.post(
            `_action/${this.getApiBasePath()}/sync/${salesChannelId}`,
            null,
            {
                ...this.basicConfig,
                headers
            }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    startProductSync(salesChannelId) {
        const headers = this.getBasicHeaders();

        return this.httpClient.post(
            `_action/${this.getApiBasePath()}/sync/${salesChannelId}/products`,
            null,
            {
                ...this.basicConfig,
                headers
            }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    startInventorySync(salesChannelId) {
        const headers = this.getBasicHeaders();

        return this.httpClient.post(
            `_action/${this.getApiBasePath()}/sync/${salesChannelId}/inventory`,
            null,
            {
                ...this.basicConfig,
                headers
            }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    startImageSync(salesChannelId) {
        const headers = this.getBasicHeaders();

        return this.httpClient.post(
            `_action/${this.getApiBasePath()}/sync/${salesChannelId}/images`,
            null,
            {
                ...this.basicConfig,
                headers
            }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    startLogCleanup(salesChannelId) {
        const headers = this.getBasicHeaders();

        return this.httpClient.post(
            `_action/${this.getApiBasePath()}/log/cleanup/${salesChannelId}`,
            null,
            {
                ...this.basicConfig,
                headers
            }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    abortSync(runId) {
        const headers = this.getBasicHeaders();

        return this.httpClient.post(
            `_action/${this.getApiBasePath()}/sync/abort/${runId}`,
            null,
            {
                ...this.basicConfig,
                headers
            }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    resetSync(salesChannelId) {
        const headers = this.getBasicHeaders();

        return this.httpClient.post(
            `_action/${this.getApiBasePath()}/sync/reset/${salesChannelId}`,
            null,
            {
                ...this.basicConfig,
                headers
            }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    getProductLog(salesChannelId, page = 1, limit = 10) {
        const headers = this.getBasicHeaders();

        return this.httpClient.get(
            `${this.getApiBasePath()}/product-log/${salesChannelId}`,
            {
                ...this.basicConfig,
                headers,
                params: { page, limit }
            }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }
}

export default SwagPayPalPosApiService;
