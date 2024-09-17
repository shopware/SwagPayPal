import template from './swag-paypal-checkout-method.html.twig';
import './swag-paypal-checkout-method.scss';

const { Context } = Shopware;

export default Shopware.Component.wrapComponentConfig({
    template,

    inject: [
        'acl',
        'repositoryFactory',
    ],

    mixins: [
        Shopware.Mixin.getByName('notification'),
    ],

    props: {
        paymentMethod: {
            type: Object as PropType<TEntity<'payment_method'>>,
            required: true,
        },
        onboardingStatus: {
            type: String,
            required: false,
            default: 'inactive',
        },
        actualConfigData: {
            type: Object,
            required: true,
            default: () => { return {}; },
        },
    },

    data() {
        return {
            merchantIntegrations: {},
            isAlertActive: !localStorage.getItem('domain-association-hidden'),
        };
    },

    computed: {
        isApplePayAndActive() {
            const handlerElements = this.paymentMethod.formattedHandlerIdentifier.split('_');

            return this.paymentMethod.active && handlerElements[handlerElements.length - 1] === 'applepayhandler';
        },

        isPayPalPui() {
            return this.paymentMethod.formattedHandlerIdentifier?.split('_').pop() === 'puihandler';
        },

        paymentMethodRepository(): TRepository<'payment_method'> {
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

        paymentMethodToggleDisabled() {
            // should be able to deactivate active payment method
            if (this.paymentMethod.active) {
                return false;
            }

            return !this.showEditLink;
        },

        showEditLink() {
            return ['active', 'limited', 'mybank'].includes(this.onboardingStatus);
        },

        statusBadgeVariant() {
            switch (this.onboardingStatus) {
                case 'active':
                    return 'success';
                case 'limited':
                case 'mybank':
                    return 'danger';
                case 'inactive':
                case 'ineligible':
                    return 'neutral';
                case 'pending':
                    return 'info';
                default:
                    return 'neutral';
            }
        },

        statusBadgeColor() {
            switch (this.onboardingStatus) {
                case 'active':
                    return '#37D046';
                case 'limited':
                case 'mybank':
                    return '#ff9800';
                case 'inactive':
                case 'ineligible':
                    return '#52667A';
                case 'pending':
                    return '#189eff';
                default:
                    return '#189eff';
            }
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
            if (!this.paymentMethod.formattedHandlerIdentifier) {
                return null;
            }

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
            if (this.isApplePayAndActive) {
                localStorage.removeItem('domain-association-hidden');
                this.isAlertActive = true;
            }

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

        hideDomainAssociationAlert() {
            localStorage.setItem('domain-association-hidden', 'true');
            this.isAlertActive = false;
        },
    },
});
