import ApiService from 'src/core/service/api/api.service';

/**
 * Gateway for the API end point "swag_paypal_setting_general"
 * @class
 * @extends ApiService
 */
class SwagPayPalSettingGeneralService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'swag-paypal-setting-general') {
        super(httpClient, loginService, apiEndpoint);
    }
}

export default SwagPayPalSettingGeneralService;
