import ApiService from 'src/core/service/api.service';

class SwagPayPalPaymentService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'paypal') {
        super(httpClient, loginService, apiEndpoint);
    }

    getPaymentDetails(orderId, payPalPaymentId) {
        const apiRoute = `${this.getApiBasePath()}/payment-details/${orderId}/${payPalPaymentId}`;
        return this.httpClient.get(
            apiRoute,
            {
                headers: this.getBasicHeaders()
            }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    capturePayment(orderId, resourceType, resourceId, captureAmount, currency, isFinalCapture) {
        const apiRoute = `_action/${this.getApiBasePath()}/capture-payment/${resourceType}/${resourceId}/${orderId}`;

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

    refundPayment(orderId, resourceType, resourceId, refundAmount, currency) {
        const apiRoute = `_action/${this.getApiBasePath()}/refund-payment/${resourceType}/${resourceId}/${orderId}`;

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

    voidPayment(orderId, resourceType, resourceId) {
        const apiRoute = `_action/${this.getApiBasePath()}/void-payment/${resourceType}/${resourceId}/${orderId}`;

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
