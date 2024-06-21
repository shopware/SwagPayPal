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
        return this.httpClient.post(
            `_action/${this.getApiBasePath()}/validate-api-credentials`,
            { apiKey, salesChannelId },
            { headers: this.getBasicHeaders() },
        ).then(ApiService.handleResponse.bind(this));
    }

    /**
     * Fetch necessary information for the sales channel from Zettle (e.g. currency)
     * and insert into salesChannel Object
     *
     * @param {Object} salesChannel
     * @param {Boolean} forceLanguage
     * @returns {Promise|Object}
     */
    fetchInformation(salesChannel, forceLanguage = false) {
        return this.httpClient.post(
            `${this.getApiBasePath()}/fetch-information`,
            { apiKey: salesChannel.extensions.paypalPosSalesChannel.apiKey },
            { headers: this.getBasicHeaders() },
        ).then((response) => {
            const data = ApiService.handleResponse(response);
            delete data.extensions;

            if (data.languageId !== null && (salesChannel.id === null || forceLanguage)) {
                salesChannel.languages.length = 0;
                salesChannel.languages.push({
                    id: data.languageId,
                });
            } else {
                delete data.languageId;
            }

            Object.assign(salesChannel, data);

            salesChannel.currencies.length = 0;
            salesChannel.currencies.push({
                id: data.currencyId,
            });

            salesChannel.countries.length = 0;
            salesChannel.countries.push({
                id: data.countryId,
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
        return this.httpClient.post(
            `_action/${this.getApiBasePath()}/clone-product-visibility`,
            { fromSalesChannelId, toSalesChannelId },
            { headers: this.getBasicHeaders() },
        ).then(ApiService.handleResponse.bind(this));
    }

    /**
     * Get product count from Zettle and cloned Sales Channel
     *
     * @param {String} salesChannelId
     * @param {String|null} cloneSalesChannelId
     * @returns {Promise|Object}
     */
    getProductCount(salesChannelId, cloneSalesChannelId) {
        return this.httpClient.get(
            `${this.getApiBasePath()}/product-count`,
            {
                params: { salesChannelId, cloneSalesChannelId },
                headers: this.getBasicHeaders(),
            },
        ).then(ApiService.handleResponse.bind(this));
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
            'WRITE:PRODUCT',
        ];

        // eslint-disable-next-line max-len
        return `https://my.izettle.com/apps/api-keys?name=Shopware%20integration&scopes=${scopes.join('%20')}&utm_source=local_partnership&utm_medium=ecommerce&utm_campaign=shopware`;
    }
}

export default SwagPayPalPosSettingApiService;
