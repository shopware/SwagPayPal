import template from './swag-paypal-checkout.html.twig';
import './swag-paypal-checkout.scss';

const { Component, Context } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('swag-paypal-checkout', {
    template,

    inject: [
        'acl',
        'repositoryFactory',
        'SwagPayPalApiCredentialsService',
    ],

    mixins: [
        'notification',
        'swag-paypal-credentials-loader',
    ],

    props: {
        actualConfigData: {
            type: Object,
            required: true,
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
        allowShowCredentials: {
            type: Boolean,
            required: false,
            default: false,
        },
    },

    data() {
        return {
            showCredentials: false,
            paymentMethods: [],
            iconMap: {
                'Swag\\PayPal\\Checkout\\Payment\\PayPalPaymentHandler': 'paypal-payment-method-paypal',
                'Swag\\PayPal\\Checkout\\Payment\\Method\\ACDCHandler': 'paypal-payment-method-credit-and-debit-card',
                'Swag\\PayPal\\Checkout\\Payment\\Method\\PUIHandler': 'paypal-payment-method-pay-upon-invoice',
                'Swag\\PayPal\\Checkout\\Payment\\Method\\BancontactAPMHandler': 'paypal-payment-method-bancontact',
                'Swag\\PayPal\\Checkout\\Payment\\Method\\BlikAPMHandler': 'paypal-payment-method-blik',
                'Swag\\PayPal\\Checkout\\Payment\\Method\\EpsAPMHandler': 'paypal-payment-method-eps',
                'Swag\\PayPal\\Checkout\\Payment\\Method\\GiropayAPMHandler': 'paypal-payment-method-giropay',
                'Swag\\PayPal\\Checkout\\Payment\\Method\\IdealAPMHandler': 'paypal-payment-method-iDEAL',
                'Swag\\PayPal\\Checkout\\Payment\\Method\\MultibancoAPMHandler': 'paypal-payment-method-multibanco',
                'Swag\\PayPal\\Checkout\\Payment\\Method\\MyBankAPMHandler': 'paypal-payment-method-mybank',
                'Swag\\PayPal\\Checkout\\Payment\\Method\\OxxoAPMHandler': 'paypal-payment-method-oxxo',
                'Swag\\PayPal\\Checkout\\Payment\\Method\\P24APMHandler': 'paypal-payment-method-p24',
                'Swag\\PayPal\\Checkout\\Payment\\Method\\SofortAPMHandler': 'paypal-payment-method-sofort',
                'Swag\\PayPal\\Checkout\\Payment\\Method\\TrustlyAPMHandler': 'paypal-payment-method-trustly',
                'Swag\\PayPal\\Checkout\\Payment\\Method\\SEPAHandler': 'paypal-payment-method-sepa',
            },
            merchantIntegrations: {},
            plusDeprecationModalOpen: false,
            showHintMerchantIdMustBeEnteredManually: false,
        };
    },

    computed: {
        pluginRepository() {
            return this.repositoryFactory.create('plugin');
        },

        paymentMethodRepository() {
            return this.repositoryFactory.create('payment_method');
        },

        pluginCriteria() {
            const criteria = new Criteria();

            return criteria.addFilter(
                Criteria.equals('name', 'SwagPayPal'),
            );
        },

        isLive() {
            return !this.isSandbox;
        },

        isSandbox() {
            return this.actualConfigData['SwagPayPal.settings.sandbox'];
        },

        isOnboardingPPCPFinished() {
            const sepaPaymentMethod = this.paymentMethods
                .find((pm) => pm.handlerIdentifier === 'Swag\\PayPal\\Checkout\\Payment\\Method\\SEPAHandler');

            if (!sepaPaymentMethod) {
                return false;
            }

            return this.onboardingStatus(sepaPaymentMethod) === 'active';
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
        async createdComponent() {
            await this.getPaymentMethodsAndMerchantIntegrations();
        },

        updateShowCredentials() {
            this.$emit('on-change-credentials-visibility', this.showCredentials);
        },

        deactivatePayPalPlus() {
            this.$set(this.actualConfigData, 'SwagPayPal.settings.plusCheckoutEnabled', false);
            this.$emit('on-deactivate-payal-plus');

            this.plusDeprecationModalOpen = false;
        },

        async getPaymentMethodsAndMerchantIntegrations() {
            await this.fetchMerchantIntegrations();
            await this.getPaymentMethods();
        },

        async getPluginData() {
            const pluginData = await this.pluginRepository.search(this.pluginCriteria, Context.api)
                .then((response) => {
                    return response;
                });

            return pluginData.first();
        },

        async getPaymentMethods() {
            const pluginData = await this.getPluginData();
            const criteria = new Criteria();

            criteria.addFilter(
                Criteria.equals('pluginId', pluginData.id),
            );
            criteria.addSorting(Criteria.sort('position', 'ASC'), true);

            this.paymentMethods = await this.paymentMethodRepository.search(criteria, Context.api)
                .then((response) => {
                    return response;
                });
        },

        async fetchMerchantIntegrations() {
            this.merchantIntegrations = await this.SwagPayPalApiCredentialsService
                .getMerchantIntegrations()
                .then((response) => {
                    return response;
                });
        },

        icon(paymentMethod) {
            return this.iconMap[paymentMethod.handlerIdentifier];
        },

        editLink(paymentMethod) {
            return {
                name: 'sw.settings.payment.detail',
                params: {
                    id: paymentMethod.id,
                },
            };
        },

        needsOnboarding(paymentMethod) {
            return this.onboardingStatus(paymentMethod)?.toUpperCase() !== 'ACTIVE';
        },

        paymentMethodToggleDisabled(paymentMethod) {
            // should be able to deactivate active payment method
            if (paymentMethod.active) {
                return false;
            }

            return this.needsOnboarding(paymentMethod);
        },

        onboardingStatus(paymentMethod) {
            return this.merchantIntegrations[paymentMethod.id];
        },

        onChangePaymentMethodActive(paymentMethod) {
            paymentMethod.active = !paymentMethod.active;

            this.paymentMethodRepository.save(paymentMethod, Context.api)
                .then(() => {
                    const state = paymentMethod.active ? 'active' : 'inactive';

                    this.createNotificationSuccess({
                        message: this.$tc(
                            `swag-paypal.settingForm.checkout.paymentMethodStatusChangedSuccess.${state}`,
                            0,
                            { name: paymentMethod.name },
                        ),
                    });
                });
        },

        statusBadgeVariant(paymentMethod) {
            let variant;

            switch (this.onboardingStatus(paymentMethod)) {
                case 'active': variant = 'success'; break;
                case 'inactive': variant = 'neutral'; break;
                case 'pending': variant = 'info'; break;
                default: variant = 'neutral';
            }

            return variant;
        },

        statusBadgeColor(paymentMethod) {
            let variant;

            switch (this.onboardingStatus(paymentMethod)) {
                case 'active':
                    variant = '#37D046';
                    break;
                case 'inactive':
                    variant = '#52667A';
                    break;
                case 'pending':
                    variant = '#189eff';
                    break;
                default:
                    variant = '#189eff';
            }

            return variant;
        },

        onboardingStatusText(paymentMethod) {
            const status = this.onboardingStatus(paymentMethod);

            return this.$tc(`swag-paypal.settingForm.checkout.onboardingStatusText.${status}`);
        },

        showEditLink(paymentMethod) {
            return this.onboardingStatus(paymentMethod) === 'active';
        },

        showPUIToolTip(paymentMethod) {
            if (paymentMethod.handlerIdentifier !== 'Swag\\PayPal\\Checkout\\Payment\\Method\\PUIHandler') {
                return false;
            }

            return this.onboardingStatus(paymentMethod) === 'inactive';
        },

        closeModal() {
            this.plusDeprecationModalOpen = false;
        },

        onPayPalCredentialsLoadSuccess(clientId, clientSecret, sandbox) {
            if (sandbox) {
                this.$set(this.actualConfigData, 'SwagPayPal.settings.clientIdSandbox', clientId);
                this.$set(this.actualConfigData, 'SwagPayPal.settings.clientSecretSandbox', clientSecret);
            } else {
                this.$set(this.actualConfigData, 'SwagPayPal.settings.clientId', clientId);
                this.$set(this.actualConfigData, 'SwagPayPal.settings.clientSecret', clientSecret);
            }
        },

        onPayPalCredentialsLoadFailed(sandbox) {
            if (sandbox) {
                this.$set(this.actualConfigData, 'SwagPayPal.settings.clientIdSandbox', '');
                this.$set(this.actualConfigData, 'SwagPayPal.settings.clientSecretSandbox', '');
                this.$set(this.actualConfigData, 'SwagPayPal.settings.merchantPayerIdSandbox', '');
            } else {
                this.$set(this.actualConfigData, 'SwagPayPal.settings.clientId', '');
                this.$set(this.actualConfigData, 'SwagPayPal.settings.clientSecret', '');
                this.$set(this.actualConfigData, 'SwagPayPal.settings.merchantPayerId', '');
            }
            this.createNotificationError({
                message: this.$tc('swag-paypal.settingForm.credentials.button.messageFetchedError'),
                duration: 10000,
            });
        },

        onNewMerchantIdReceived(merchantId, sandbox) {
            if (sandbox) {
                this.$set(this.actualConfigData, 'SwagPayPal.settings.merchantPayerIdSandbox', merchantId);
            } else {
                this.$set(this.actualConfigData, 'SwagPayPal.settings.merchantPayerId', merchantId);
            }
        },

        optimisticSave() {
            const cfg = this.actualConfigData;
            const suffix = cfg['SwagPayPal.settings.sandbox'] ? 'Sandbox' : '';

            const clientIdExists = !!cfg[`SwagPayPal.settings.clientId${suffix}`];
            const clientSecretExists = !!cfg[`SwagPayPal.settings.clientSecret${suffix}`];
            const merchantIdExists = !!cfg[`SwagPayPal.settings.merchantPayerId${suffix}`];

            const allowSave = clientIdExists
                            && clientSecretExists
                            && merchantIdExists;

            if (allowSave) {
                this.$emit('on-save-settings');

                return;
            }

            if (!merchantIdExists) {
                this.showHintMerchantIdMustBeEnteredManually = true;
            }
        },

        checkBoolFieldInheritance(value) {
            return typeof value !== 'boolean';
        },
    },
});
