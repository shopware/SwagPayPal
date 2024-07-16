import type { LoginService } from 'src/core/service/login.service';
import type { AxiosInstance } from 'axios';
import type * as PayPal from 'src/types';

const ApiService = Shopware.Classes.ApiService;

class SwagPayPalPaymentService extends ApiService {
    constructor(httpClient: AxiosInstance, loginService: LoginService, apiEndpoint = 'paypal') {
        super(httpClient, loginService, apiEndpoint);
    }

    getPaymentDetails(orderId: string, payPalPaymentId: string) {
        return this.httpClient.get<PayPal.Api.Operations<'paymentDetails'>>(
            `${this.getApiBasePath()}/payment-details/${orderId}/${payPalPaymentId}`,
            { headers: this.getBasicHeaders() },
        ).then(ApiService.handleResponse.bind(this));
    }

    capturePayment(
        orderId: string,
        resourceType: string,
        resourceId: string,
        captureAmount: number,
        currency: string,
        captureIsFinal: boolean,
    ) {
        return this.httpClient.post<PayPal.Api.Operations<'paypalCapturePayment'>>(
            `_action/${this.getApiBasePath()}/capture-payment/${resourceType}/${resourceId}/${orderId}`,
            { captureAmount, currency, captureIsFinal },
            { headers: this.getBasicHeaders() },
        ).then(ApiService.handleResponse.bind(this));
    }

    refundPayment(
        orderId: string,
        resourceType: string,
        resourceId: string,
        refundAmount: number,
        currency: string,
        description: string,
        reason: string,
        refundInvoiceNumber: string,
    ) {
        return this.httpClient.post<PayPal.Api.Operations<'paypalRefundPayment'>>(
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

    voidPayment(orderId: string, resourceType: string, resourceId: string) {
        return this.httpClient.post<PayPal.Api.Operations<'paypalVoidPayment'>>(
            `_action/${this.getApiBasePath()}/void-payment/${resourceType}/${resourceId}/${orderId}`,
            {},
            { headers: this.getBasicHeaders() },
        ).then(ApiService.handleResponse.bind(this));
    }
}

export default SwagPayPalPaymentService;
