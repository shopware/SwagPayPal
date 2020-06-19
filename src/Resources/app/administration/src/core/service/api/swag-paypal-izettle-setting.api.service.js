const ApiService = Shopware.Classes.ApiService;

class SwagPayPalIZettleSettingApiService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'paypal/izettle') {
        super(httpClient, loginService, apiEndpoint);
    }

    /**
     * Checks, if an access token for this user data can be created
     *
     * @param {string} apiKey
     * @returns {Promise|Object}
     */
    validateApiCredentials(apiKey) {
        const headers = this.getBasicHeaders();

        return this.httpClient
            .post(`_action/${this.getApiBasePath()}/validate-api-credentials`, { apiKey }, { headers })
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }

    /**
     * Fetch necessary information for the sales channel from iZettle (e.g. currency)
     * and insert into salesChannel Object
     *
     * @param {Object} salesChannel
     * @returns {Promise|Object}
     */
    fetchInformation(salesChannel) {
        const headers = this.getBasicHeaders();
        const apiKey = salesChannel.extensions.paypalIZettleSalesChannel.apiKey;

        return this.httpClient
            .post(`_action/${this.getApiBasePath()}/fetch-information`, { apiKey }, { headers })
            .then((response) => {
                const data = ApiService.handleResponse(response);

                salesChannel.currencyId = data.currencyId;
                salesChannel.currencies.length = 0;
                salesChannel.currencies.push({
                    id: data.currencyId
                });

                return data;
            });
    }

    /**
     * @returns {string}
     */
    generateApiUrl() {
        const scopes = [
            'READ:PURCHASE',
            'READ:FINANCE',
            'READ:USERINFO',
            'READ:PRODUCT',
            'WRITE:PRODUCT'
        ];

        return `https://my.izettle.com/apps/api-keys?name=Shopware%20integration&scopes=${scopes.join('%20')}`;
    }
}

export default SwagPayPalIZettleSettingApiService;
