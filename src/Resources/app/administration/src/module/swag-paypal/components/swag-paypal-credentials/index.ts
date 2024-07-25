import type * as PayPal from 'src/types';
import template from './swag-paypal-credentials.html.twig';

export default Shopware.Component.wrapComponentConfig({
    template,

    inject: [
        'acl',
        'SwagPayPalApiCredentialsService',
    ],

    mixins: [
        Shopware.Mixin.getByName('swag-paypal-notification'),
    ],

    props: {
        actualConfigData: {
            type: Object as PropType<PayPal.SystemConfig>,
            required: true,
            default: () => { return {}; },
        },
        allConfigs: {
            type: Object as PropType<Record<string, PayPal.SystemConfig>>,
            required: true,
        },
        selectedSalesChannelId: {
            type: String,
            required: false,
            default: null,
        },
        clientIdErrorState: {
            type: Object as PropType<PayPal.ErrorState>,
            required: false,
            default: null,
        },
        clientSecretErrorState: {
            type: Object as PropType<PayPal.ErrorState>,
            required: false,
            default: null,
        },
        clientIdSandboxErrorState: {
            type: Object as PropType<PayPal.ErrorState>,
            required: false,
            default: null,
        },
        clientSecretSandboxErrorState: {
            type: Object as PropType<PayPal.ErrorState>,
            required: false,
            default: null,
        },
        clientIdFilled: {
            type: Boolean,
            required: true,
        },
        clientSecretFilled: {
            type: Boolean,
            required: true,
        },
        clientIdSandboxFilled: {
            type: Boolean,
            required: true,
        },
        clientSecretSandboxFilled: {
            type: Boolean,
            required: true,
        },
        isLoading: {
            type: Boolean,
            required: true,
        },
    },

    data() {
        return {
            isTestingLive: false,
            isTestingSandbox: false,
            isTestLiveSuccessful: false,
            isTestSandboxSuccessful: false,
        };
    },

    computed: {
        testLiveButtonDisabled() {
            return this.isLoading || !this.clientSecretFilled || !this.clientIdFilled || this.isTestingLive;
        },

        testSandboxButtonDisabled() {
            return this.isLoading || !this.clientSecretSandboxFilled || !this.clientIdSandboxFilled || this.isTestingSandbox;
        },
    },

    methods: {
        checkTextFieldInheritance(value: unknown): boolean {
            if (typeof value !== 'string') {
                return true;
            }

            return value.length <= 0;
        },

        checkBoolFieldInheritance(value: unknown): boolean {
            return typeof value !== 'boolean';
        },

        onTest(sandbox: boolean) {
            if (sandbox) {
                this.isTestingSandbox = true;
            } else {
                this.isTestingLive = true;
            }

            const sandboxSetting = sandbox ? 'Sandbox' : '';
            const clientId = this.actualConfigData[`SwagPayPal.settings.clientId${sandboxSetting}`] ||
                    this.allConfigs?.null[`SwagPayPal.settings.clientId${sandboxSetting}`];
            const clientSecret = this.actualConfigData[`SwagPayPal.settings.clientSecret${sandboxSetting}`] ||
                    this.allConfigs?.null[`SwagPayPal.settings.clientSecret${sandboxSetting}`];

            this.SwagPayPalApiCredentialsService.validateApiCredentials(
                clientId,
                clientSecret,
                sandbox,
            ).then((response) => {
                if (response.credentialsValid !== true) {
                    return;
                }

                if (sandbox) {
                    this.isTestingSandbox = false;
                    this.isTestSandboxSuccessful = true;
                } else {
                    this.isTestingLive = false;
                    this.isTestLiveSuccessful = true;
                }
            }).catch((errorResponse: PayPal.ServiceError) => {
                this.createNotificationFromError({ errorResponse, title: 'swag-paypal.settingForm.messageTestError' });

                if (sandbox) {
                    this.isTestingSandbox = false;
                    this.isTestSandboxSuccessful = false;
                } else {
                    this.isTestingLive = false;
                    this.isTestLiveSuccessful = false;
                }
            });
        },
    },
});
