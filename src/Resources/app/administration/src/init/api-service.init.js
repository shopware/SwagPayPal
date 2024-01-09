import SwagPayPalApiCredentialsService from '../core/service/api/swag-paypal-api-credentials.service';
import SwagPayPalPosSettingApiService from '../core/service/api/swag-paypal-pos-setting.api.service';
import SwagPayPalPosApiService from '../core/service/api/swag-paypal-pos.api.service';
import SwagPayPalPosWebhookRegisterService from '../core/service/api/swag-paypal-pos-webhook-register.service';
import SwagPayPalWebhookService from '../core/service/api/swag-paypal-webhook.service';
import SwagPayPalPaymentService from '../core/service/api/swag-paypal-payment.service';
import SwagPayPalOrderService from '../core/service/api/swag-paypal-order.service';
import SwagPaypalPaymentMethodService from '../core/service/api/swag-paypal-payment-method.service';
import SwagPayPalDisputeApiService from '../core/service/api/swag-paypal-dispute.api.service';

const { Application } = Shopware;

const initContainer = Application.getContainer('init');

Application.addServiceProvider(
    'SwagPayPalApiCredentialsService',
    (container) => new SwagPayPalApiCredentialsService(initContainer.httpClient, container.loginService),
);

Application.addServiceProvider(
    'SwagPayPalPosSettingApiService',
    (container) => new SwagPayPalPosSettingApiService(initContainer.httpClient, container.loginService),
);

Application.addServiceProvider(
    'SwagPayPalPosApiService',
    (container) => new SwagPayPalPosApiService(initContainer.httpClient, container.loginService),
);

Application.addServiceProvider(
    'SwagPayPalPosWebhookRegisterService',
    (container) => new SwagPayPalPosWebhookRegisterService(initContainer.httpClient, container.loginService),
);

Application.addServiceProvider(
    'SwagPayPalWebhookService',
    (container) => new SwagPayPalWebhookService(initContainer.httpClient, container.loginService),
);

Application.addServiceProvider(
    'SwagPayPalPaymentService',
    (container) => new SwagPayPalPaymentService(initContainer.httpClient, container.loginService),
);

Application.addServiceProvider(
    'SwagPayPalOrderService',
    (container) => new SwagPayPalOrderService(initContainer.httpClient, container.loginService),
);

Application.addServiceProvider(
    'SwagPaypalPaymentMethodService',
    (container) => new SwagPaypalPaymentMethodService(initContainer.httpClient, container.loginService),
);

Application.addServiceProvider(
    'SwagPayPalDisputeApiService',
    (container) => new SwagPayPalDisputeApiService(initContainer.httpClient, container.loginService),
);
