import template from './swag-paypal-payment-method.html.twig';
import './swag-paypal-payment-method.scss';

export default Shopware.Component.wrapComponentConfig({
    template,

    inject: [
        'acl',
    ],

    emits: ['update:active'],

    props: {
        paymentMethod: {
            type: Object as PropType<TEntity<'payment_method'>>,
            required: true,
        },
    },

    computed: {
        merchantInformationStore() {
            return Shopware.Store.get('swagPayPalMerchantInformation');
        },

        onboardingStatus() {
            return this.merchantInformationStore.capabilities[this.paymentMethod.id];
        },

        identifier(): string {
            return this.paymentMethod.formattedHandlerIdentifier?.split('_').pop() ?? '';
        },

        isPui() {
            return this.identifier === 'puihandler';
        },

        paymentMethodToggleDisabled() {
            // should be able to deactivate active payment method
            if (this.paymentMethod.active) {
                return false;
            }

            return !this.showEditLink;
        },

        showEditLink() {
            return ['active', 'limited', 'mybank', 'test'].includes(this.onboardingStatus);
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
                case 'test':
                case 'pending':
                    return 'info';
                default:
                    return 'neutral';
            }
        },

        onboardingStatusText() {
            return this.$t(`swag-paypal-method.onboardingStatusText.${this.onboardingStatus}`);
        },

        onboardingStatusTooltip() {
            const snippetKey = `swag-paypal-method.onboardingStatusTooltip.${this.onboardingStatus}`;

            if (!this.$te(snippetKey)) {
                return null;
            }

            return this.$t(snippetKey);
        },

        availabilityToolTip() {
            const snippetKey = `swag-paypal-method.availabilityToolTip.${this.identifier}`;

            if (!this.$te(snippetKey)) {
                return null;
            }

            return this.$t(snippetKey);
        },
    },

    methods: {
        onUpdateActive(active: boolean) {
            // update:value is emitted twice
            if (this.paymentMethod.active !== active) {
                this.$emit('update:active', active);
            }
        },
    },
});
