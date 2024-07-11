import template from './swag-paypal-credentials.html.twig';

export default Shopware.Component.wrapComponentConfig({
    template,

    inject: [
        'acl',
        'SwagPayPalApiCredentialsService',
    ],

    mixins: [
        Shopware.Mixin.getByName('notification'),
    ],

    props: {
        actualConfigData: {
            type: Object,
            required: true,
            default: () => { return {}; },
        },
        allConfigs: {
            type: Object,
            required: true,
        },
        selectedSalesChannelId: {
            type: String,
            required: false,
            default: null,
        },
        clientIdErrorState: {
            type: Object,
            required: false,
            default: null,
        },
        clientSecretErrorState: {
            type: Object,
            required: false,
            default: null,
        },
        clientIdSandboxErrorState: {
            type: Object,
            required: false,
            default: null,
        },
        clientSecretSandboxErrorState: {
            type: Object,
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
        checkTextFieldInheritance(value) {
            if (typeof value !== 'string') {
                return true;
            }

            return value.length <= 0;
        },

        checkBoolFieldInheritance(value) {
            return typeof value !== 'boolean';
        },

        onTest(sandbox) {
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
            }).catch((errorResponse) => {
                if (!errorResponse.response?.data?.errors) {
                    return;
                }

                let message = `<b>${this.$tc('swag-paypal.settingForm.messageTestError')}</b> `;
                message += errorResponse.response.data.errors.map((error) => error.detail).join(' / ');

                this.createNotificationError({ message });

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
