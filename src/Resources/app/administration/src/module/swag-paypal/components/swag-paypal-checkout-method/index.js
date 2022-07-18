import template from './swag-paypal-checkout-method.html.twig';
import './swag-paypal-checkout-method.scss';

const { Component, Context } = Shopware;

Component.register('swag-paypal-checkout-method', {
    template,

    inject: [
        'acl',
        'repositoryFactory',
    ],

    mixins: [
        'notification',
    ],

    props: {
        paymentMethod: {
            type: Object,
            required: true,
        },
        onboardingStatus: {
            type: String,
            required: false,
            default: 'inactive',
        },
    },

    data() {
        return {
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
        };
    },

    computed: {
        paymentMethodRepository() {
            return this.repositoryFactory.create('payment_method');
        },

        icon() {
            return this.iconMap[this.paymentMethod.formattedHandlerIdentifier];
        },

        editLink() {
            return {
                name: 'sw.settings.payment.detail',
                params: {
                    id: this.paymentMethod.id,
                },
            };
        },

        needsOnboarding() {
            return this.onboardingStatus?.toUpperCase() !== 'ACTIVE';
        },

        paymentMethodToggleDisabled() {
            // should be able to deactivate active payment method
            if (this.paymentMethod.active) {
                return false;
            }

            return this.needsOnboarding;
        },

        showEditLink() {
            return this.onboardingStatus === 'active';
        },

        statusBadgeVariant() {
            let variant;

            switch (this.onboardingStatus) {
                case 'active': variant = 'success'; break;
                case 'limited': variant = 'danger'; break;
                case 'inactive': case 'ineligible': variant = 'neutral'; break;
                case 'pending': variant = 'info'; break;
                default: variant = 'neutral';
            }

            return variant;
        },

        statusBadgeColor() {
            let variant;

            switch (this.onboardingStatus) {
                case 'active':
                    variant = '#37D046';
                    break;
                case 'limited':
                    variant = '#ff9800';
                    break;
                case 'inactive':
                case 'ineligible':
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

        onboardingStatusText() {
            return this.$tc(`swag-paypal.settingForm.checkout.onboardingStatusText.${this.onboardingStatus}`);
        },

        onboardingStatusTooltip() {
            const snippetKey = `swag-paypal.settingForm.checkout.onboardingStatusTooltip.${this.onboardingStatus}`;

            if (!this.$te(snippetKey)) {
                return null;
            }

            return this.$tc(snippetKey);
        },

        availabilityToolTip() {
            const handlerElements = this.paymentMethod.formattedHandlerIdentifier.split('_');
            const handlerName = handlerElements[handlerElements.length - 1];
            const snippetKey = `swag-paypal.settingForm.checkout.availabilityToolTip.${handlerName}`;

            if (!this.$te(snippetKey)) {
                return null;
            }

            return this.$tc(snippetKey);
        },
    },

    methods: {
        onChangePaymentMethodActive() {
            this.paymentMethod.active = !this.paymentMethod.active;

            this.paymentMethodRepository.save(this.paymentMethod, Context.api)
                .then(() => {
                    const state = this.paymentMethod.active ? 'active' : 'inactive';

                    this.createNotificationSuccess({
                        message: this.$tc(
                            `swag-paypal.settingForm.checkout.paymentMethodStatusChangedSuccess.${state}`,
                            0,
                            { name: this.paymentMethod.name },
                        ),
                    });
                });
        },
    },
});
