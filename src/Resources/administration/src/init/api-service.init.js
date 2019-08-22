import SwagPayPalWebhookRegisterService
    from '../../src/core/service/api/swag-paypal-webhook-register.service';
import SwagPayPalApiCredentialsService
    from '../../src/core/service/api/swag-paypal-api-credentials.service';
import SwagPayPalPaymentService
    from '../../src/core/service/api/swag-paypal-payment.service';

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
