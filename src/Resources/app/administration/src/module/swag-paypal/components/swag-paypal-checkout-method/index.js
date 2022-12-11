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
            merchantIntegrations: {},
        };
    },

    computed: {
        paymentMethodRepository() {
            return this.repositoryFactory.create('payment_method');
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
