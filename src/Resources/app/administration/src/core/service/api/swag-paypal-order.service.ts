const ApiService = Shopware.Classes.ApiService;

class SwagPayPalOrderService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'paypal-v2') {
        super(httpClient, loginService, apiEndpoint);
    }

    /**
     * @param {String} orderTransactionId
     * @param {String} paypalOrderId
     */
    getOrderDetails(orderTransactionId, paypalOrderId) {
        const apiRoute = `${this.getApiBasePath()}/order/${orderTransactionId}/${paypalOrderId}`;

        return this.httpClient.get(
            apiRoute,
            this.getDefaultOptions(),
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    /**
     * @param {String} orderTransactionId
     * @param {String} captureId
     * @param {String} paypalOrderId
     * @param {String} currency
     * @param {String|Number} amount
     * @param {String} invoiceNumber
     * @param {String} noteToPayer
     * @param {String} partnerAttributionId
     */
    refundCapture(
        orderTransactionId,
        captureId,
        paypalOrderId,
        currency,
        amount,
        invoiceNumber,
        noteToPayer,
        partnerAttributionId,
    ) {
        const params = `/${orderTransactionId}/${captureId}/${paypalOrderId}`;
        const apiRoute = `${this.getApiBasePath('', '_action')}/refund-capture${params}`;

        return this.doPostRequest(apiRoute, partnerAttributionId, {
            currency,
            amount,
            invoiceNumber,
            noteToPayer,
        });
    }

    /**
     * @param {String} orderTransactionId
     * @param {String} authorizationId
     * @param {String} currency
     * @param {String|Number} amount
     * @param {String} invoiceNumber
     * @param {String} noteToPayer
     * @param {String} partnerAttributionId
     * @param {Boolean} isFinal
     */
    captureAuthorization(
        orderTransactionId,
        authorizationId,
        currency,
        amount,
        invoiceNumber,
        noteToPayer,
        partnerAttributionId,
        isFinal,
    ) {
        const params = `/${orderTransactionId}/${authorizationId}`;
        const apiRoute = `${this.getApiBasePath('', '_action')}/capture-authorization${params}`;

        return this.doPostRequest(apiRoute, partnerAttributionId, {
            currency,
            amount,
            invoiceNumber,
            noteToPayer,
            isFinal,
        });
    }

    /**
     * @param {String} orderTransactionId
     * @param {String} authorizationId
     * @param {String} partnerAttributionId
     */
    voidAuthorization(orderTransactionId, authorizationId, partnerAttributionId) {
        const params = `/${orderTransactionId}/${authorizationId}`;
        const apiRoute = `${this.getApiBasePath('', '_action')}/void-authorization${params}`;

        return this.doPostRequest(apiRoute, partnerAttributionId);
    }

    /**
     * @param {String} apiRoute
     * @param {String} partnerAttributionId
     * @param {Object} requestParameters
     */
    doPostRequest(apiRoute, partnerAttributionId, requestParameters = {}) {
        const params = { partnerAttributionId, ...requestParameters };
        return this.httpClient.post(
            apiRoute,
            params,
            this.getDefaultOptions(),
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    getDefaultOptions() {
        return {
            headers: this.getBasicHeaders(),
            version: Shopware.Context.api.apiVersion,
        };
    }
}

export default SwagPayPalOrderService;
