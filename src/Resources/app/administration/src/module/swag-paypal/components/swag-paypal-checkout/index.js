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
                handler_swag_paypalpaymenthandler: 'paypal-payment-method-paypal',
                handler_swag_acdchandler: 'paypal-payment-method-credit-and-debit-card',
                handler_swag_puihandler: 'paypal-payment-method-pay-upon-invoice',
                handler_swag_bancontactapmhandler: 'paypal-payment-method-bancontact',
                handler_swag_blikapmhandler: 'paypal-payment-method-blik',
                handler_swag_epsapmhandler: 'paypal-payment-method-eps',
                handler_swag_giropayapmhandler: 'paypal-payment-method-giropay',
                handler_swag_idealapmhandler: 'paypal-payment-method-iDEAL',
                handler_swag_multibancoapmhandler: 'paypal-payment-method-multibanco',
                handler_swag_mybankapmhandler: 'paypal-payment-method-mybank',
                handler_swag_oxxoapmhandler: 'paypal-payment-method-oxxo',
                handler_swag_p24apmhandler: 'paypal-payment-method-p24',
                handler_swag_sofortapmhandler: 'paypal-payment-method-sofort',
                handler_swag_trustlyapmhandler: 'paypal-payment-method-trustly',
                handler_swag_sepahandler: 'paypal-payment-method-sepa',
            },
            merchantIntegrations: {},
            plusDeprecationModalOpen: false,
            showHintMerchantIdMustBeEnteredManually: false,
        };
    },

    computed: {
        paymentMethodRepository() {
            return this.repositoryFactory.create('payment_method');
        },

        paymentMethodCriteria() {
            const criteria = new Criteria();

            criteria.addFilter(Criteria.equals('plugin.name', 'SwagPayPal'));
            criteria.addSorting(Criteria.sort('position', 'ASC'), true);

            return criteria;
        },

        isLive() {
            return !this.isSandbox;
        },

        isSandbox() {
            return this.actualConfigData['SwagPayPal.settings.sandbox'];
        },

        isOnboardingPPCPFinished() {
            const sepaPaymentMethod = this.paymentMethods
                .find((pm) => pm.formattedHandlerIdentifier === 'handler_swag_sepahandler');

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
            this.$set(this.actualConfigData, 'SwagPayPal.settings.merchantLocation', 'other');
            this.$set(this.actualConfigData, 'SwagPayPal.settings.spbAlternativePaymentMethodsEnabled', false);
            this.$emit('on-deactivate-paypal-plus');

            this.plusDeprecationModalOpen = false;
        },

        async getPaymentMethodsAndMerchantIntegrations() {
            await this.fetchMerchantIntegrations();
            await this.getPaymentMethods();
        },

        async getPaymentMethods() {
            this.paymentMethods = await this.paymentMethodRepository.search(this.paymentMethodCriteria, Context.api)
                .then((response) => {
                    return response.filter((paymentMethod) => {
                        return paymentMethod.formattedHandlerIdentifier !== 'handler_swag_pospayment';
                    });
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
            return this.iconMap[paymentMethod.formattedHandlerIdentifier];
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
                case 'limited': variant = 'danger'; break;
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
                case 'limited':
                    variant = '#ff9800';
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

        availabilityToolTip(paymentMethod) {
            const handlerElements = paymentMethod.formattedHandlerIdentifier.split('_');
            const handlerName = handlerElements[handlerElements.length - 1];
            const snippetKey = `swag-paypal.settingForm.checkout.availabilityToolTip.${handlerName}`;

            if (!this.$te(snippetKey)) {
                return null;
            }

            return this.$tc(snippetKey);
        },

        closeModal() {
            this.plusDeprecationModalOpen = false;
        },

        onPayPalCredentialsLoadSuccess(clientId, clientSecret, merchantPayerId, sandbox) {
            this.setConfig(clientId, clientSecret, merchantPayerId, sandbox);
            this.$emit('on-save-settings');
        },

        onPayPalCredentialsLoadFailed(sandbox) {
            this.setConfig('', '', '', sandbox);
            this.createNotificationError({
                message: this.$tc('swag-paypal.settingForm.credentials.button.messageFetchedError'),
                duration: 10000,
            });
        },

        setConfig(clientId, clientSecret, merchantPayerId, sandbox) {
            const suffix = sandbox ? 'Sandbox' : '';
            this.$set(this.actualConfigData, `SwagPayPal.settings.clientId${suffix}`, clientId);
            this.$set(this.actualConfigData, `SwagPayPal.settings.clientSecret${suffix}`, clientSecret);
            this.$set(this.actualConfigData, `SwagPayPal.settings.merchantPayerId${suffix}`, merchantPayerId);
        },

        checkBoolFieldInheritance(value) {
            return typeof value !== 'boolean';
        },
    },
});
