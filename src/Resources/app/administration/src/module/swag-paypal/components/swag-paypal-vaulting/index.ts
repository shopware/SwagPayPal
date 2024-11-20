import type * as PayPal from 'src/types';
import template from './swag-paypal-vaulting.html.twig';
import './swag-paypal-vaulting.scss';

export default Shopware.Component.wrapComponentConfig({
    template,

    inject: [
        'acl',
        'SwagPayPalApiCredentialsService',
    ],

    mixins: [
        Shopware.Mixin.getByName('swag-paypal-notification'),
        Shopware.Mixin.getByName('swag-paypal-credentials-loader'),
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
        isSaveSuccessful: {
            type: Boolean,
            required: true,
        },
    },

    data(): {
        merchantInformation: PayPal.Setting<'merchant_information'> | null;
        isLoadingMerchantInformation: boolean;
    } {
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
                .getMerchantInformation(this.selectedSalesChannelId)
                .catch((errorResponse: PayPal.ServiceError) => {
                    this.createNotificationFromError({ errorResponse });

                    return null;
                });
        },

        /**
         * @deprecated tag:v10.0.0 - Will be removed and is replaced by swag-paypal-inherit-wrapper
         */
        checkBoolFieldInheritance(value: unknown): boolean {
            return typeof value !== 'boolean';
        },
    },
});
