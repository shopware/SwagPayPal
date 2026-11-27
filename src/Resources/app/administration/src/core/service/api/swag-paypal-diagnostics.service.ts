import type { LoginService } from 'src/core/service/login.service';
import type { AxiosInstance } from 'axios';
import type * as PayPal from 'SwagPayPal/types';
import type { operations } from 'SwagPayPal/types/openapi';

export type WebhookTestTarget = operations['sendPayPalTestWebhook']['requestBody']['content']['application/json']['target'];

const ApiService = Shopware.Classes.ApiService;

export default class SwagPayPalDiagnosticsService extends ApiService {
    constructor(httpClient: AxiosInstance, loginService: LoginService, apiEndpoint = 'paypal') {
        super(httpClient, loginService, apiEndpoint);
    }

    expressCallback(salesChannelId: string): Promise<PayPal.Api.Operations<'getPayPalExpressCallbackDiagnostics'>> {
        return this.httpClient.get<PayPal.Api.Operations<'getPayPalExpressCallbackDiagnostics'>>(
            `_action/${this.getApiBasePath()}/diagnostics/express-callback/${salesChannelId}`,
            { headers: this.getBasicHeaders() },
        ).then(ApiService.handleResponse.bind(this));
    }

    expressCallbackReachability(salesChannelId: string): Promise<PayPal.Api.Operations<'testPayPalExpressCallbackReachability'>> {
        return this.httpClient.post<PayPal.Api.Operations<'testPayPalExpressCallbackReachability'>>(
            `_action/${this.getApiBasePath()}/diagnostics/express-callback/${salesChannelId}/reachability`,
            {},
            { headers: this.getBasicHeaders() },
        ).then(ApiService.handleResponse.bind(this));
    }

    webhook(salesChannelId: string): Promise<PayPal.Api.Operations<'getPayPalWebhookDiagnostics'>> {
        return this.httpClient.get<PayPal.Api.Operations<'getPayPalWebhookDiagnostics'>>(
            `_action/${this.getApiBasePath()}/diagnostics/webhook/${salesChannelId}`,
            { headers: this.getBasicHeaders() },
        ).then(ApiService.handleResponse.bind(this));
    }

    webhookReachability(salesChannelId: string): Promise<PayPal.Api.Operations<'testPayPalWebhookReachability'>> {
        return this.httpClient.post<PayPal.Api.Operations<'testPayPalWebhookReachability'>>(
            `_action/${this.getApiBasePath()}/diagnostics/webhook/${salesChannelId}/reachability`,
            {},
            { headers: this.getBasicHeaders() },
        ).then(ApiService.handleResponse.bind(this));
    }

    webhookTest(salesChannelId: string, target: WebhookTestTarget): Promise<PayPal.Api.Operations<'sendPayPalTestWebhook'>> {
        return this.httpClient.post<PayPal.Api.Operations<'sendPayPalTestWebhook'>>(
            `_action/${this.getApiBasePath()}/diagnostics/webhook/${salesChannelId}/test`,
            { target },
            { headers: this.getBasicHeaders() },
        ).then(ApiService.handleResponse.bind(this));
    }

    webhookTestArrival(salesChannelId: string): Promise<PayPal.Api.Operations<'getPayPalTestWebhookArrival'>> {
        return this.httpClient.get<PayPal.Api.Operations<'getPayPalTestWebhookArrival'>>(
            `_action/${this.getApiBasePath()}/diagnostics/webhook/${salesChannelId}/test`,
            { headers: this.getBasicHeaders() },
        ).then(ApiService.handleResponse.bind(this));
    }

    twig(salesChannelId: string): Promise<PayPal.Api.Operations<'getPayPalTwigDiagnostics'>> {
        return this.httpClient.get<PayPal.Api.Operations<'getPayPalTwigDiagnostics'>>(
            `_action/${this.getApiBasePath()}/diagnostics/twig/${salesChannelId}`,
            { headers: this.getBasicHeaders() },
        ).then(ApiService.handleResponse.bind(this));
    }
}
