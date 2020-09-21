import SwagPayPalWebhookRegisterService
    from '../core/service/api/swag-paypal-webhook-register.service';
import SwagPayPalApiCredentialsService
    from '../core/service/api/swag-paypal-api-credentials.service';
import SwagPayPalPosSettingApiService
    from '../core/service/api/swag-paypal-pos-setting.api.service';
import SwagPayPalPosApiService
    from '../core/service/api/swag-paypal-pos.api.service';
import SwagPayPalPosWebhookRegisterService
    from '../core/service/api/swag-paypal-pos-webhook-register.service';
import SwagPayPalPaymentService
    from '../core/service/api/swag-paypal-payment.service';
import SwagPaypalPaymentMethodServiceService
    from '../core/service/api/swag-paypal-payment-method.service';

const { Application } = Shopware;

const initContainer = Application.getContainer('init');

Application.addServiceProvider(
    'SwagPayPalWebhookRegisterService',
    (container) => new SwagPayPalWebhookRegisterService(initContainer.httpClient, container.loginService)
);

Application.addServiceProvider(
    'SwagPayPalApiCredentialsService',
    (container) => new SwagPayPalApiCredentialsService(initContainer.httpClient, container.loginService)
);

Application.addServiceProvider(
    'SwagPayPalPosSettingApiService',
    (container) => new SwagPayPalPosSettingApiService(initContainer.httpClient, container.loginService)
);

Application.addServiceProvider(
    'SwagPayPalPosApiService',
    (container) => new SwagPayPalPosApiService(initContainer.httpClient, container.loginService)
);

Application.addServiceProvider(
    'SwagPayPalPosWebhookRegisterService',
    (container) => new SwagPayPalPosWebhookRegisterService(initContainer.httpClient, container.loginService)
);

Application.addServiceProvider(
    'SwagPayPalPaymentService',
    (container) => new SwagPayPalPaymentService(initContainer.httpClient, container.loginService)
);

Application.addServiceProvider(
    'SwagPaypalPaymentMethodServiceService',
    (container) => new SwagPaypalPaymentMethodServiceService(initContainer.httpClient, container.loginService)
);
