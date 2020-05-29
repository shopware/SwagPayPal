const ApiService = Shopware.Classes.ApiService;

class SwagPayPalIZettleApiService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'paypal/izettle') {
        super(httpClient, loginService, apiEndpoint);
        this.basicConfig = {
            timeout: 300000
        };
    }

    startSync(salesChannelId) {
        const headers = this.getBasicHeaders();

        return this.httpClient.get(
            `${this.getApiBasePath()}/sync/${salesChannelId}`,
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

        return this.httpClient.get(
            `${this.getApiBasePath()}/sync/${salesChannelId}/products`,
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

        return this.httpClient.get(
            `${this.getApiBasePath()}/sync/${salesChannelId}/inventory`,
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

        return this.httpClient.get(
            `${this.getApiBasePath()}/log/cleanup/${salesChannelId}`,
            {
                ...this.basicConfig,
                headers
            }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }
}

export default SwagPayPalIZettleApiService;
