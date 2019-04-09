import { Application } from 'src/core/shopware';
import SwagPayPalWebhookRegisterService
    from '../../src/core/service/api/swag-paypal-webhook-register.service';
import SwagPayPalValidateApiCredentialsService
    from '../../src/core/service/api/swag-paypal-validate-api-credentials.service';
import SwagPayPalPaymentService
    from '../../src/core/service/api/swag-paypal-payment.service';

Application.addServiceProvider('SwagPayPalWebhookRegisterService', (container) => {
    const initContainer = Application.getContainer('init');

    return new SwagPayPalWebhookRegisterService(initContainer.httpClient, container.loginService);
});

Application.addServiceProvider('SwagPayPalValidateApiCredentialsService', (container) => {
    const initContainer = Application.getContainer('init');

    return new SwagPayPalValidateApiCredentialsService(initContainer.httpClient, container.loginService);
});

Application.addServiceProvider('SwagPayPalPaymentService', (container) => {
    const initContainer = Application.getContainer('init');

    return new SwagPayPalPaymentService(initContainer.httpClient, container.loginService);
});
