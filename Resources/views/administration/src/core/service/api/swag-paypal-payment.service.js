import ApiService from 'src/core/service/api.service';

class SwagPayPalPaymentService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'paypal') {
        super(httpClient, loginService, apiEndpoint);
    }

    getPaymentDetails(payPalPaymentId) {
        const apiRoute = `${this.getApiBasePath()}/payment-details/${payPalPaymentId}`;
        return this.httpClient.get(
            apiRoute,
            {
                headers: this.getBasicHeaders()
            }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }
}

export default SwagPayPalPaymentService;
