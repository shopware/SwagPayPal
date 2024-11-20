import type * as PayPal from 'src/types';
import template from './swag-paypal-checkout.html.twig';
import './swag-paypal-checkout.scss';

const { Context } = Shopware;
const { Criteria } = Shopware.Data;

export default Shopware.Component.wrapComponentConfig({
    template,

    inject: [
        'acl',
        'repositoryFactory',
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
            required: false,
            default: false,
        },
        clientSecretFilled: {
            type: Boolean,
            required: false,
            default: false,
        },
        clientIdSandboxFilled: {
            type: Boolean,
            required: false,
            default: false,
        },
        clientSecretSandboxFilled: {
            type: Boolean,
            required: false,
            default: false,
        },
        isLoading: {
            type: Boolean,
            required: true,
        },
    },

    data(): {
        paymentMethods: TEntity<'payment_method'>[];
        merchantInformation: PayPal.Setting<'merchant_information'>;
        plusDeprecationModalOpen: boolean;
        showHintMerchantIdMustBeEnteredManually: boolean;
        isLoadingPaymentMethods: boolean;
    } {
        return {
            paymentMethods: [],
            merchantInformation: {
                merchantIntegrations: {
                    legalName: null,
                    primaryEmail: null,
                },
                capabilities: {},
            },
            plusDeprecationModalOpen: false,
            showHintMerchantIdMustBeEnteredManually: false,
            isLoadingPaymentMethods: false,
        };
    },

    computed: {
        assetFilter() {
            return Shopware.Filter.getByName('asset');
        },

        paymentMethodRepository(): TRepository<'payment_method'> {
            return this.repositoryFactory.create('payment_method');
        },

        paymentMethodCriteria(): TCriteria {
            const criteria = new Criteria(1, 500);

            criteria.addAssociation('media');

            criteria.addFilter(Criteria.equals('plugin.name', 'SwagPayPal'));
            criteria.addSorting(Criteria.sort('position', 'ASC'), true);

            return criteria;
        },

        isLive(): boolean {
            return !this.isSandbox;
        },

        isSandbox(): boolean {
            return this.actualConfigData['SwagPayPal.settings.sandbox'] ?? false;
        },

        liveButtonTitle(): string {
            if (!this.actualConfigData['SwagPayPal.settings.clientSecret']) {
                return this.$tc('swag-paypal.settingForm.checkout.button.liveTitle');
            }

            if (this.isSandbox) {
                return this.$tc('swag-paypal.settingForm.checkout.button.changeLiveTitle');
            }

            if (!this.isOnboardingPPCPFinished) {
                return this.$tc('swag-paypal.settingForm.checkout.button.onboardingLiveTitle');
            }

            if (this.paymentMethods.some((pm) => this.onboardingStatus(pm) !== 'active')) {
                return this.$tc('swag-paypal.settingForm.checkout.button.restartOnboardingLiveTitle');
            }

            return this.$tc('swag-paypal.settingForm.checkout.button.changeLiveTitle');
        },

        sandboxButtonTitle(): string {
            if (!this.actualConfigData['SwagPayPal.settings.clientSecretSandbox']) {
                return this.$tc('swag-paypal.settingForm.checkout.button.sandboxTitle');
            }

            if (this.isLive) {
                return this.$tc('swag-paypal.settingForm.checkout.button.changeSandboxTitle');
            }

            if (!this.isOnboardingPPCPFinished) {
                return this.$tc('swag-paypal.settingForm.checkout.button.onboardingSandboxTitle');
            }

            if (this.paymentMethods.find((pm) => this.onboardingStatus(pm) !== 'active')) {
                return this.$tc('swag-paypal.settingForm.checkout.button.restartOnboardingSandboxTitle');
            }

            return this.$tc('swag-paypal.settingForm.checkout.button.changeSandboxTitle');
        },

        sandboxToggleDisabled(): boolean {
            return ((!this.actualConfigData['SwagPayPal.settings.clientSecret']
                        && !!this.actualConfigData['SwagPayPal.settings.clientSecretSandbox']
                        && this.isSandbox)
                || (!!this.actualConfigData['SwagPayPal.settings.clientSecret']
                        && !this.actualConfigData['SwagPayPal.settings.clientSecretSandbox']
                        && this.isLive))
                && !this.selectedSalesChannelId;
        },

        isOnboardingPPCPFinished(): boolean {
            const sepaPaymentMethod = this.paymentMethods
                .find((pm) => pm.formattedHandlerIdentifier === 'handler_swag_sepahandler');

            if (!sepaPaymentMethod) {
                return false;
            }

            return this.onboardingStatus(sepaPaymentMethod) === 'active';
        },

        showMerchantInformation() {
            return this.isOnboardingPPCPFinished;
        },

        showSandboxToggle() {
            return this.actualConfigData['SwagPayPal.settings.clientSecret']
                || this.actualConfigData['SwagPayPal.settings.clientSecretSandbox']
                || this.selectedSalesChannelId;
        },

        merchantEmail() {
            return this.merchantInformation.merchantIntegrations.primary_email
                ?? this.merchantInformation.merchantIntegrations.tracking_id;
        },
    },

    watch: {
        isSandbox() {
            this.$emit('on-save-settings');
        },

        isOnboardingPPCPFinished() {
            // open the deactivate PayPalPLUS modal if ppcp onboarding was successful and PayPalPlus is still active
            const plusCheckoutEnabled = this.actualConfigData['SwagPayPal.settings.plusCheckoutEnabled'];

            if (!plusCheckoutEnabled) {
                return;
            }

            this.plusDeprecationModalOpen = plusCheckoutEnabled && this.isOnboardingPPCPFinished;
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.getPaymentMethodsAndMerchantIntegrations();
        },

        deactivatePayPalPlus() {
            this.$set(this.actualConfigData, 'SwagPayPal.settings.plusCheckoutEnabled', false);
            this.$set(this.actualConfigData, 'SwagPayPal.settings.merchantLocation', 'other');
            this.$set(this.actualConfigData, 'SwagPayPal.settings.spbAlternativePaymentMethodsEnabled', false);
            this.$emit('on-deactivate-paypal-plus');

            this.plusDeprecationModalOpen = false;
        },

        async getPaymentMethodsAndMerchantIntegrations() {
            this.isLoadingPaymentMethods = true;
            await Promise.all([this.fetchMerchantIntegrations(), this.getPaymentMethods()]);
            this.isLoadingPaymentMethods = false;
        },

        async getPaymentMethods() {
            const response = await this.paymentMethodRepository.search(this.paymentMethodCriteria, Context.api);

            this.paymentMethods = response.filter((pm) => {
                if (pm.formattedHandlerIdentifier === 'handler_swag_pospayment') {
                    return false;
                }

                return !([
                    'handler_swag_trustlyapmhandler',
                    'handler_swag_giropayapmhandler',
                    'handler_swag_sofortapmhandler',
                ].includes(pm.formattedHandlerIdentifier ?? '') && !pm.active);
            });
        },

        fetchMerchantIntegrations() {
            return this.SwagPayPalApiCredentialsService.getMerchantInformation(this.selectedSalesChannelId)
                .then((merchantInformation) => {
                    this.merchantInformation = merchantInformation;
                    this.merchantIntegrations = merchantInformation.capabilities;
                })
                .catch((errorResponse: PayPal.ServiceError) => {
                    this.createNotificationFromError({ errorResponse });
                });
        },

        onboardingStatus(paymentMethod: TEntity<'payment_method'>): string {
            return this.merchantInformation.capabilities[paymentMethod.id];
        },

        async onChangePaymentMethodActive(paymentMethod: TEntity<'payment_method'>) {
            paymentMethod.active = !paymentMethod.active;

            await this.paymentMethodRepository.save(paymentMethod, Context.api);

            const state = paymentMethod.active ? 'active' : 'inactive';

            this.createNotificationSuccess({
                message: this.$tc(
                    `swag-paypal.settingForm.checkout.paymentMethodStatusChangedSuccess.${state}`,
                    0,
                    { name: paymentMethod.name },
                ),
            });
        },

        closeModal() {
            this.plusDeprecationModalOpen = false;
        },

        onPayPalCredentialsLoadSuccess(clientId?: string, clientSecret?: string, merchantPayerId?: string, sandbox?: boolean) {
            this.setConfig(clientId, clientSecret, merchantPayerId, sandbox);
            this.$emit('on-save-settings');
        },

        onPayPalCredentialsLoadFailed(sandbox: boolean) {
            this.setConfig('', '', '', sandbox);
            this.createNotificationError({
                message: this.$tc('swag-paypal.settingForm.credentials.button.messageFetchedError'),
                duration: 10000,
            });
        },

        setConfig(clientId?: string, clientSecret?: string, merchantPayerId?: string, sandbox?: boolean) {
            const suffix = sandbox ? 'Sandbox' : '';
            this.$set(this.actualConfigData, `SwagPayPal.settings.clientId${suffix}`, clientId);
            this.$set(this.actualConfigData, `SwagPayPal.settings.clientSecret${suffix}`, clientSecret);
            this.$set(this.actualConfigData, `SwagPayPal.settings.merchantPayerId${suffix}`, merchantPayerId);
        },

        /**
         * @deprecated tag:v10.0.0 - Will be removed and is replaced by swag-paypal-inherit-wrapper
         */
        checkBoolFieldInheritance(value: unknown): boolean {
            return typeof value !== 'boolean';
        },
    },
});
