import SwagPayPalWebhookRegisterService
    from '../core/service/api/swag-paypal-webhook-register.service';
import SwagPayPalApiCredentialsService
    from '../core/service/api/swag-paypal-api-credentials.service';
import SwagPayPalIZettleSettingApiService
    from '../core/service/api/swag-paypal-izettle-setting.api.service';
import SwagPayPalIZettleApiService
    from '../core/service/api/swag-paypal-izettle.api.service';
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
    'SwagPayPalIZettleSettingApiService',
    (container) => new SwagPayPalIZettleSettingApiService(initContainer.httpClient, container.loginService)
);

Application.addServiceProvider(
    'SwagPayPalIZettleApiService',
    (container) => new SwagPayPalIZettleApiService(initContainer.httpClient, container.loginService)
);

Application.addServiceProvider(
    'SwagPayPalPaymentService',
    (container) => new SwagPayPalPaymentService(initContainer.httpClient, container.loginService)
);

Application.addServiceProvider(
    'SwagPaypalPaymentMethodServiceService',
    (container) => new SwagPaypalPaymentMethodServiceService(initContainer.httpClient, container.loginService)
);
