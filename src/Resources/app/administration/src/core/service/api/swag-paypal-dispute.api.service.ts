import type { LoginService } from 'src/core/service/login.service';
import type { AxiosInstance } from 'axios';
import type * as PayPal from 'src/types';

const ApiService = Shopware.Classes.ApiService;

class SwagPayPalDisputeApiService extends ApiService {
    constructor(httpClient: AxiosInstance, loginService: LoginService, apiEndpoint = 'paypal/dispute') {
        super(httpClient, loginService, apiEndpoint);
    }

    /**
     * Get a list of all disputes.
     * Provide a sales channel ID if you have different merchant accounts for your sales channels.
     * Disputes could also be filtered by their state.
     */
    list(salesChannelId: string | null = null, disputeStateFilter: string | null = null) {
        return this.httpClient.get<PayPal.Api.Operations<'disputeList'>>(
            this.getApiBasePath(),
            {
                params: { salesChannelId, disputeStateFilter },
                headers: this.getBasicHeaders(),
            },
        ).then(ApiService.handleResponse.bind(this));
    }

    /**
     * Get the details of a dispute
     */
    detail(disputeId: string, salesChannelId: string | null) {
        return this.httpClient.get<PayPal.Api.Operations<'disputeDetails'>>(
            `${this.getApiBasePath()}/${disputeId}`,
            {
                params: { salesChannelId },
                headers: this.getBasicHeaders(),
            },
        ).then(ApiService.handleResponse.bind(this));
    }
}

export default SwagPayPalDisputeApiService;
