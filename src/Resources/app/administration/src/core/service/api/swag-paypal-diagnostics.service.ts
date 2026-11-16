import type { LoginService } from 'src/core/service/login.service';
import type { AxiosInstance } from 'axios';

const ApiService = Shopware.Classes.ApiService;

export default class SwagPayPalDiagnosticsService extends ApiService {
    constructor(httpClient: AxiosInstance, loginService: LoginService, apiEndpoint = 'paypal') {
        super(httpClient, loginService, apiEndpoint);
    }
}
