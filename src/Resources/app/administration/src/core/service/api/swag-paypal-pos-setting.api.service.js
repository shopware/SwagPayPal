const ApiService = Shopware.Classes.ApiService;

class SwagPayPalPosSettingApiService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'paypal/pos') {
        super(httpClient, loginService, apiEndpoint);
    }

    /**
     * Checks, if an access token for this user data can be created
     *
     * @param {string} apiKey
     * @param {string|null} salesChannelId
     * @returns {Promise|Object}
     */
    validateApiCredentials(apiKey, salesChannelId = null) {
        const headers = this.getBasicHeaders();

        return this.httpClient
            .post(`_action/${this.getApiBasePath()}/validate-api-credentials`, { apiKey, salesChannelId }, { headers })
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
        const apiKey = salesChannel.extensions.paypalPosSalesChannel.apiKey;

        return this.httpClient
            .post(`${this.getApiBasePath()}/fetch-information`, { apiKey }, { headers })
            .then((response) => {
                const data = ApiService.handleResponse(response);
                delete data.extensions;

                Object.assign(salesChannel, data);

                salesChannel.currencies.length = 0;
                salesChannel.currencies.push({
                    id: data.currencyId
                });

                salesChannel.languages.length = 0;
                salesChannel.languages.push({
                    id: data.languageId
                });

                salesChannel.countries.length = 0;
                salesChannel.countries.push({
                    id: data.countryId
                });

                return data;
            });
    }

    /**
     * Clone product visibilility from one sales channel to another
     *
     * @param {String} toSalesChannelId
     * @param {String} fromSalesChannelId
     * @returns {Promise|Object}
     */
    cloneProductVisibility(fromSalesChannelId, toSalesChannelId) {
        const headers = this.getBasicHeaders();

        return this.httpClient
            .post(
                `_action/${this.getApiBasePath()}/clone-product-visibility`,
                { fromSalesChannelId, toSalesChannelId },
                { headers }
            ).then((response) => {
                return ApiService.handleResponse(response);
            });
    }

    /**
     * Get product count from iZettle and cloned Sales Channel
     *
     * @param {String} salesChannelId
     * @param {String|null} cloneSalesChannelId
     * @returns {Promise|Object}
     */
    getProductCount(salesChannelId, cloneSalesChannelId) {
        const headers = this.getBasicHeaders();

        return this.httpClient
            .get(
                `${this.getApiBasePath()}/product-count`,
                {
                    params: { salesChannelId, cloneSalesChannelId },
                    headers
                }
            ).then((response) => {
                return ApiService.handleResponse(response);
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

export default SwagPayPalPosSettingApiService;
