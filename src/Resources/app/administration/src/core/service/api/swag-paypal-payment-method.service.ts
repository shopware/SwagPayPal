import type * as PayPal from 'src/types';
import type { LoginService } from 'src/core/service/login.service';
import type { AxiosInstance } from 'axios';

const ApiService = Shopware.Classes.ApiService;

class SwagPaypalPaymentMethodService extends ApiService {
    constructor(httpClient: AxiosInstance, loginService: LoginService, apiEndpoint = 'paypal') {
        super(httpClient, loginService, apiEndpoint);
    }

    /**
     * Sets the default payment method to PayPal for the given Sales Channel id.
     */
    setDefaultPaymentForSalesChannel(salesChannelId: string | null = null) {
        return this.httpClient.post<PayPal.Api.Operations<'setPayPalAsDefault'>>(
            `_action/${this.getApiBasePath()}/saleschannel-default`,
            { salesChannelId },
            { headers: this.getBasicHeaders() },
        ).then(ApiService.handleResponse.bind(this));
    }
}

export default SwagPaypalPaymentMethodService;
