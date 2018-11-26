import { Application } from 'src/core/shopware';
import SwagPayPalSettingGeneralService from '../../src/core/service/api/swag-paypal-setting-general.api.service';
import SwagPayPalApiService from '../../src/core/service/api/swag-paypal-api.service';
import SwagPayPalValidateApiService from '../../src/core/service/api/swag-paypal-validate-api-service';

Application.addServiceProvider('swagPaypalSettingGeneralService', (container) => {
    const initContainer = Application.getContainer('init');

    return new SwagPayPalSettingGeneralService(initContainer.httpClient, container.loginService);
});

Application.addServiceProvider('swagPayPalApiService', (container) => {
    const initContainer = Application.getContainer('init');

    return new SwagPayPalApiService(initContainer.httpClient, container.loginService);
});

Application.addServiceProvider('swagPayPalValidateApiService', (container) => {
    const initContainer = Application.getContainer('init');

    return new SwagPayPalValidateApiService(initContainer.httpClient, container.loginService);
});
