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
        /**
         * @deprecated tag:v10.0.0 - Will be removed and is replaced by swag-paypal-inherit-wrapper
         */
        checkTextFieldInheritance(value: unknown): boolean {
            if (typeof value !== 'string') {
                return true;
            }

            return value.length <= 0;
        },

        /**
         * @deprecated tag:v10.0.0 - Will be removed and is replaced by swag-paypal-inherit-wrapper
         */
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
            const merchantPayerId = this.actualConfigData[`SwagPayPal.settings.merchantPayerId${sandboxSetting}`] ||
                    this.allConfigs?.null[`SwagPayPal.settings.merchantPayerId${sandboxSetting}`] || null;

            this.SwagPayPalApiCredentialsService.validateApiCredentials(
                clientId,
                clientSecret,
                sandbox,
                merchantPayerId,
            ).then((response) => {
                if (sandbox) {
                    this.isTestSandboxSuccessful = !!response.credentialsValid;
                } else {
                    this.isTestLiveSuccessful = !!response.credentialsValid;
                }
            }).catch((errorResponse: PayPal.ServiceError) => {
                this.createNotificationFromError({ errorResponse, title: 'swag-paypal.settingForm.messageTestError' });

                if (sandbox) {
                    this.isTestSandboxSuccessful = false;
                } else {
                    this.isTestLiveSuccessful = false;
                }
            }).finally(() => {
                if (sandbox) {
                    this.isTestingSandbox = false;
                } else {
                    this.isTestingLive = false;
                }
            });
        },
    },
});
