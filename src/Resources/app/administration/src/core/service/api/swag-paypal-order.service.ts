import type { LoginService } from 'src/core/service/login.service';
import type { AxiosInstance } from 'axios';
import type * as PayPal from 'src/types';

const ApiService = Shopware.Classes.ApiService;

class SwagPayPalOrderService extends ApiService {
    constructor(httpClient: AxiosInstance, loginService: LoginService, apiEndpoint = 'paypal-v2') {
        super(httpClient, loginService, apiEndpoint);
    }

    getOrderDetails(orderTransactionId: string, paypalOrderId: string) {
        return this.httpClient.get<PayPal.Api.Operations<'orderDetails'>>(
            `${this.getApiBasePath()}/order/${orderTransactionId}/${paypalOrderId}`,
            {
                headers: this.getBasicHeaders(),
            },
        ).then(ApiService.handleResponse.bind(this));
    }

    refundCapture(
        orderTransactionId: string,
        captureId: string,
        paypalOrderId: string,
        currency: string,
        amount: string | number,
        invoiceNumber: string,
        noteToPayer: string,
        partnerAttributionId: string,
    ) {
        return this.doPostRequest<PayPal.Api.Operations<'refundCapture'>>(
            `${this.getApiBasePath('', '_action')}/refund-capture/${orderTransactionId}/${captureId}/${paypalOrderId}`,
            partnerAttributionId,
            { currency, amount, invoiceNumber, noteToPayer },
        );
    }

    captureAuthorization(
        orderTransactionId: string,
        authorizationId: string,
        currency: string,
        amount: string | number,
        invoiceNumber: string,
        noteToPayer: string,
        partnerAttributionId: string,
        isFinal: boolean,
    ) {
        return this.doPostRequest<PayPal.Api.Operations<'captureAuthorization'>>(
            `${this.getApiBasePath('', '_action')}/capture-authorization/${orderTransactionId}/${authorizationId}`,
            partnerAttributionId,
            { currency, amount, invoiceNumber, noteToPayer, isFinal },
        );
    }

    voidAuthorization(orderTransactionId: string, authorizationId: string, partnerAttributionId: string) {
        return this.doPostRequest<PayPal.Api.Operations<'voidAuthorization'>>(
            `${this.getApiBasePath('', '_action')}/void-authorization/${orderTransactionId}/${authorizationId}`,
            partnerAttributionId,
        );
    }

    doPostRequest<D>(apiRoute: string, partnerAttributionId: string, requestParameters: object = {}) {
        return this.httpClient.post<D>(
            apiRoute,
            { partnerAttributionId, ...requestParameters },
            {
                headers: this.getBasicHeaders(),
            },
        ).then(ApiService.handleResponse.bind(this));
    }
}

export default SwagPayPalOrderService;
