import template from './swag-paypal-vaulting.html.twig';
import './swag-paypal-vaulting.scss';

const { Component } = Shopware;

Component.register('swag-paypal-vaulting', {
    template,

    inject: [
        'acl',
        'SwagPayPalApiCredentialsService',
    ],

    mixins: [
        'swag-paypal-credentials-loader',
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
        isSaveSuccessful: {
            type: Boolean,
            required: true,
        },
    },

    data() {
        return {
            canHandleVaulting: false,
            isLoadingMerchantInformation: false,
        };
    },

    computed: {
        isSandbox() {
            return this.actualConfigData['SwagPayPal.settings.sandbox'];
        },
    },

    watch: {
        isSaveSuccessful(newState) {
            if (newState === false) {
                return;
            }

            this.createdComponent();
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        async createdComponent() {
            await this.fetchMerchantInformation();

            this.adjustRequestParams();
        },
        async fetchMerchantInformation() {
            this.isLoadingMerchantInformation = true;

            const information = await this.SwagPayPalApiCredentialsService
                .getMerchantInformation(this.selectedSalesChannelId);

            this.checkIfVaultingIsActive(information);

            this.isLoadingMerchantInformation = false;
        },
        checkIfVaultingIsActive(merchantInformation) {
            this.canHandleVaulting = false;

            merchantInformation?.merchantIntegrations?.capabilities?.forEach((capability) => {
                if (capability?.name === 'PAYPAL_WALLET_VAULTING_ADVANCED' && capability?.status === 'ACTIVE') {
                    this.canHandleVaulting = true;
                }
            });
        },
        adjustRequestParams() {
            this.requestParams.secondaryProducts = this.requestParams.secondaryProducts.concat(',advanced_vaulting');
            this.requestParams.capabilities = 'PAYPAL_WALLET_VAULTING_ADVANCED';
            this.requestParams.features = this.requestParams.features.concat('VAULT', 'BILLING_AGREEMENT');
        },
        checkBoolFieldInheritance(value) {
            return typeof value !== 'boolean';
        },
    },
});
