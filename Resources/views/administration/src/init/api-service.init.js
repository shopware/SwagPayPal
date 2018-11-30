import { Application } from 'src/core/shopware';
import SwagPayPalSettingGeneralService
    from '../../src/core/service/api/swag-paypal-setting-general.api.service';
import SwagPayPalWebhookRegisterService
    from '../../src/core/service/api/swag-paypal-webhook-register.service';
import SwagPayPalValidateApiCredentialsService
    from '../../src/core/service/api/swag-paypal-validate-api-credentials.service';

Application.addServiceProvider('swagPaypalSettingGeneralService', (container) => {
    const initContainer = Application.getContainer('init');

    return new SwagPayPalSettingGeneralService(initContainer.httpClient, container.loginService);
});

Application.addServiceProvider('SwagPayPalWebhookRegisterService', (container) => {
    const initContainer = Application.getContainer('init');

    return new SwagPayPalWebhookRegisterService(initContainer.httpClient, container.loginService);
});

Application.addServiceProvider('SwagPayPalValidateApiCredentialsService', (container) => {
    const initContainer = Application.getContainer('init');

    return new SwagPayPalValidateApiCredentialsService(initContainer.httpClient, container.loginService);
});
