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
        return this.httpClient.get(
            `${this.getApiBasePath()}/order/${orderTransactionId}/${paypalOrderId}`,
            {
                headers: this.getBasicHeaders(),
            },
        ).then(ApiService.handleResponse.bind(this));
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
        return this.doPostRequest(
            `${this.getApiBasePath('', '_action')}/refund-capture/${orderTransactionId}/${captureId}/${paypalOrderId}`,
            partnerAttributionId,
            { currency, amount, invoiceNumber, noteToPayer },
        );
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
        return this.doPostRequest(
            `${this.getApiBasePath('', '_action')}/capture-authorization/${orderTransactionId}/${authorizationId}`,
            partnerAttributionId,
            { currency, amount, invoiceNumber, noteToPayer, isFinal },
        );
    }

    /**
     * @param {String} orderTransactionId
     * @param {String} authorizationId
     * @param {String} partnerAttributionId
     */
    voidAuthorization(orderTransactionId, authorizationId, partnerAttributionId) {
        return this.doPostRequest(
            `${this.getApiBasePath('', '_action')}/void-authorization/${orderTransactionId}/${authorizationId}`,
            partnerAttributionId,
        );
    }

    /**
     * @param {String} apiRoute
     * @param {String} partnerAttributionId
     * @param {Object} requestParameters
     */
    doPostRequest(apiRoute, partnerAttributionId, requestParameters = {}) {
        return this.httpClient.post(
            apiRoute,
            { partnerAttributionId, ...requestParameters },
            {
                headers: this.getBasicHeaders(),
            },
        ).then(ApiService.handleResponse.bind(this));
    }
}

export default SwagPayPalOrderService;
