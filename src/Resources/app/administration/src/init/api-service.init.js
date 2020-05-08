import SwagPayPalWebhookRegisterService
    from '../core/service/api/swag-paypal-webhook-register.service';
import SwagPayPalApiCredentialsService
    from '../core/service/api/swag-paypal-api-credentials.service';
import SwagPayPalPaymentService
    from '../core/service/api/swag-paypal-payment.service';
import SwagPaypalPaymentMethodServiceService
    from '../core/service/api/swag-paypal-payment-method.service';

const { Application } = Shopware;

Application.addServiceProvider('SwagPayPalWebhookRegisterService', (container) => {
    const initContainer = Application.getContainer('init');

    return new SwagPayPalWebhookRegisterService(initContainer.httpClient, container.loginService);
});

Application.addServiceProvider('SwagPayPalApiCredentialsService', (container) => {
    const initContainer = Application.getContainer('init');

    return new SwagPayPalApiCredentialsService(initContainer.httpClient, container.loginService);
});

Application.addServiceProvider('SwagPayPalPaymentService', (container) => {
    const initContainer = Application.getContainer('init');

    return new SwagPayPalPaymentService(initContainer.httpClient, container.loginService);
});

Application.addServiceProvider('SwagPaypalPaymentMethodServiceService', (container) => {
    const initContainer = Application.getContainer('init');

    return new SwagPaypalPaymentMethodServiceService(initContainer.httpClient, container.loginService);
});
