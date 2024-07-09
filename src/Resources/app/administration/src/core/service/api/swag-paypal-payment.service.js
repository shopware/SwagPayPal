const ApiService = Shopware.Classes.ApiService;

class SwagPayPalPaymentService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'paypal') {
        super(httpClient, loginService, apiEndpoint);
    }

    getPaymentDetails(orderId, payPalPaymentId) {
        return this.httpClient.get(
            `${this.getApiBasePath()}/payment-details/${orderId}/${payPalPaymentId}`,
            { headers: this.getBasicHeaders() },
        ).then(ApiService.handleResponse.bind(this));
    }

    capturePayment(
        orderId,
        resourceType,
        resourceId,
        captureAmount,
        currency,
        captureIsFinal,
    ) {
        return this.httpClient.post(
            `_action/${this.getApiBasePath()}/capture-payment/${resourceType}/${resourceId}/${orderId}`,
            { captureAmount, currency, captureIsFinal },
            { headers: this.getBasicHeaders() },
        ).then(ApiService.handleResponse.bind(this));
    }

    refundPayment(
        orderId,
        resourceType,
        resourceId,
        refundAmount,
        currency,
        description,
        reason,
        refundInvoiceNumber,
    ) {
        return this.httpClient.post(
            `_action/${this.getApiBasePath()}/refund-payment/${resourceType}/${resourceId}/${orderId}`,
            {
                refundAmount,
                currency,
                description,
                reason,
                refundInvoiceNumber,
            },
            { headers: this.getBasicHeaders() },
        ).then(ApiService.handleResponse.bind(this));
    }

    voidPayment(orderId, resourceType, resourceId) {
        return this.httpClient.post(
            `_action/${this.getApiBasePath()}/void-payment/${resourceType}/${resourceId}/${orderId}`,
            {},
            { headers: this.getBasicHeaders() },
        ).then(ApiService.handleResponse.bind(this));
    }
}

export default SwagPayPalPaymentService;
