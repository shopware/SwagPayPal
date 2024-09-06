import type { LoginService } from 'src/core/service/login.service';
import type { AxiosInstance } from 'axios';
import type * as PayPal from 'src/types';

const ApiService = Shopware.Classes.ApiService;

const { EntityCollection } = Shopware.Data;

class SwagPayPalPosSettingApiService extends ApiService {
    constructor(httpClient: AxiosInstance, loginService: LoginService, apiEndpoint = 'paypal/pos') {
        super(httpClient, loginService, apiEndpoint);
    }

    /**
     * Checks, if an access token for this user data can be created
     */
    validateApiCredentials(apiKey: string, salesChannelId: string | null = null) {
        return this.httpClient.post<PayPal.Api.Operations<'posValidateApiCredentials'>>(
            `_action/${this.getApiBasePath()}/validate-api-credentials`,
            { apiKey, salesChannelId },
            { headers: this.getBasicHeaders() },
        ).then(ApiService.handleResponse.bind(this));
    }

    /**
     * Fetch necessary information for the sales channel from Zettle (e.g. currency)
     * and insert into salesChannel Object
     */
    fetchInformation(salesChannel: TEntity<'sales_channel'>, forceLanguage = false) {
        return this.httpClient.post<PayPal.Api.Operations<'posFetchInformation'>>(
            `${this.getApiBasePath()}/fetch-information`,
            { apiKey: salesChannel.extensions?.paypalPosSalesChannel?.apiKey },
            { headers: this.getBasicHeaders() },
        ).then(ApiService.handleResponse.bind(this)).then((data) => {
            delete data.extensions;

            salesChannel.languages ??= new EntityCollection('language', 'language', Shopware.Context.api);
            salesChannel.currencies ??= new EntityCollection('currency', 'currency', Shopware.Context.api);
            salesChannel.countries ??= new EntityCollection('country', 'country', Shopware.Context.api);

            if (data.languageId !== null && (salesChannel.id === null || forceLanguage)) {
                salesChannel.languages.length = 0;
                salesChannel.languages.push({
                    id: data.languageId,
                } as TEntity<'language'>);
            } else {
                delete data.languageId;
            }

            Object.assign(salesChannel, data);

            salesChannel.currencies.length = 0;
            salesChannel.currencies.push({
                id: data.currencyId,
            } as TEntity<'currency'>);

            salesChannel.countries.length = 0;
            salesChannel.countries.push({
                id: data.countryId,
            } as TEntity<'country'>);

            return data;
        });
    }

    /**
     * Clone product visibility from one sales channel to another
     */
    cloneProductVisibility(fromSalesChannelId: string, toSalesChannelId: string) {
        return this.httpClient.post<PayPal.Api.Operations<'posCloneProductVisibility'>>(
            `_action/${this.getApiBasePath()}/clone-product-visibility`,
            { fromSalesChannelId, toSalesChannelId },
            { headers: this.getBasicHeaders() },
        ).then(ApiService.handleResponse.bind(this));
    }

    /**
     * Get product count from Zettle and cloned Sales Channel
     */
    getProductCount(salesChannelId: string, cloneSalesChannelId: string | null) {
        return this.httpClient.get<PayPal.Api.Operations<'posGetProductCounts'>>(
            `${this.getApiBasePath()}/product-count`,
            {
                params: { salesChannelId, cloneSalesChannelId },
                headers: this.getBasicHeaders(),
            },
        ).then(ApiService.handleResponse.bind(this));
    }

    generateApiUrl(): string {
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
