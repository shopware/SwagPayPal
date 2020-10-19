const ApiService = Shopware.Classes.ApiService;

class SwagPayPalOrderService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'paypal-v2') {
        super(httpClient, loginService, apiEndpoint);
    }

    getOrderDetails(orderTransactionId, paypalOrderId) {
        const apiRoute = `${this.getApiBasePath()}/order/${orderTransactionId}/${paypalOrderId}`;

        return this.httpClient.get(
            apiRoute,
            this.getDefaultOptions()
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    refundCapture(
        orderTransactionId,
        captureId,
        paypalOrderId,
        currency,
        amount,
        invoiceNumber,
        noteToPayer,
        partnerAttributionId
    ) {
        const params = `/${orderTransactionId}/${captureId}/${paypalOrderId}`;
        const apiRoute = `${this.getApiBasePath('', '_action')}/refund-capture${params}`;

        return this.doPostRequest(apiRoute, partnerAttributionId, {
            currency,
            amount,
            invoiceNumber,
            noteToPayer
        });
    }

    captureAuthorization(
        orderTransactionId,
        authorizationId,
        currency,
        amount,
        invoiceNumber,
        noteToPayer,
        partnerAttributionId
    ) {
        const params = `/${orderTransactionId}/${authorizationId}`;
        const apiRoute = `${this.getApiBasePath('', '_action')}/capture-authorization${params}`;

        return this.doPostRequest(apiRoute, partnerAttributionId, {
            currency,
            amount,
            invoiceNumber,
            noteToPayer
        });
    }

    voidAuthorization(orderTransactionId, authorizationId, partnerAttributionId) {
        const params = `/${orderTransactionId}/${authorizationId}`;
        const apiRoute = `${this.getApiBasePath('', '_action')}/void-authorization${params}`;

        return this.doPostRequest(apiRoute, partnerAttributionId);
    }

    doPostRequest(apiRoute, partnerAttributionId, requestParameters = {}) {
        const params = { partnerAttributionId, ...requestParameters };
        return this.httpClient.post(
            apiRoute,
            params,
            this.getDefaultOptions()
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    getDefaultOptions() {
        return {
            headers: this.getBasicHeaders(),
            version: Shopware.Context.api.apiVersion
        };
    }
}

export default SwagPayPalOrderService;
