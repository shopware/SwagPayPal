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
        Shopware.Mixin.getByName('swag-paypal-credentials-loader'),
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
            merchantInformation: null,
            isLoadingMerchantInformation: false,
        };
    },

    computed: {
        isSandbox() {
            return this.actualConfigData['SwagPayPal.settings.sandbox'];
        },
        canHandleVaulting() {
            return this.merchantInformation?.merchantIntegrations?.capabilities?.some(
                (capability) => capability?.name === 'PAYPAL_WALLET_VAULTING_ADVANCED' && capability?.status === 'ACTIVE',
            );
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
            this.isLoadingMerchantInformation = true;
            await this.fetchMerchantInformation();
            this.isLoadingMerchantInformation = false;
        },

        async fetchMerchantInformation() {
            this.merchantInformation = await this.SwagPayPalApiCredentialsService
                .getMerchantInformation(this.selectedSalesChannelId);
        },

        checkBoolFieldInheritance(value) {
            return typeof value !== 'boolean';
        },
    },
});
