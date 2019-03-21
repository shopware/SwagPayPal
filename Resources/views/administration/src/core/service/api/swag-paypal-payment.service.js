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

    capturePayment(resourceType, resourceId, captureAmount, currency, isFinalCapture) {
        const apiRoute = `_action/${this.getApiBasePath()}/capture-payment/${resourceType}/${resourceId}`;

        return this.httpClient.post(
            apiRoute,
            {
                captureAmount: captureAmount,
                currency: currency,
                captureIsFinal: isFinalCapture
            },
            {
                headers: this.getBasicHeaders()
            }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    refundPayment(resourceType, resourceId, refundAmount, currency) {
        const apiRoute = `_action/${this.getApiBasePath()}/refund-payment/${resourceType}/${resourceId}`;

        return this.httpClient.post(
            apiRoute,
            {
                refundAmount: refundAmount,
                currency: currency
            },
            {
                headers: this.getBasicHeaders()
            }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    voidPayment(resourceType, resourceId) {
        const apiRoute = `_action/${this.getApiBasePath()}/void-payment/${resourceType}/${resourceId}`;

        return this.httpClient.post(
            apiRoute,
            {},
            {
                headers: this.getBasicHeaders()
            }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }
}

export default SwagPayPalPaymentService;
