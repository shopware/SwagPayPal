import template from './swag-paypal-method-card.html.twig';
import './swag-paypal-method-card.scss';

const { Context } = Shopware;
const { Criteria } = Shopware.Data;

export default Shopware.Component.wrapComponentConfig({
    template,

    inject: [
        'repositoryFactory',
    ],

    mixins: [
        Shopware.Mixin.getByName('notification'),
        Shopware.Mixin.getByName('swag-paypal-settings'),
        Shopware.Mixin.getByName('swag-paypal-merchant-information'),
    ],

    data(): {
        isLoadingPaymentMethods: boolean;
        paymentMethods: TEntity<'payment_method'>[];
    } {
        return {
            isLoadingPaymentMethods: true,
            paymentMethods: [],
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
            return (new Criteria(1, 500))
                .addAssociation('media')
                .addFilter(Criteria.equals('plugin.name', 'SwagPayPal'))
                .addSorting(Criteria.sort('position', 'ASC'));
        },

        merchantStatus() {
            const merchantIntegrations = this.merchantInformationStore.actual.merchantIntegrations;

            if (!merchantIntegrations) {
                return 'notConnected';
            } else if (!this.merchantInformationStore.canPPCP) {
                return 'onboardingNeeded';
            } else if (!merchantIntegrations?.primary_email_confirmed) {
                return 'emailUnconfirmed';
            } else if (!merchantIntegrations?.payments_receivable) {
                return 'paymentsUnreceivable';
            } else {
                return 'connected';
            }
        },

        statusVariant() {
            switch (this.merchantStatus) {
                case 'onboardingNeeded':
                case 'notConnected':
                    return 'danger';
                case 'emailUnconfirmed':
                case 'paymentsUnreceivable':
                    return 'warning';
                case 'connected':
                    return 'success';
            }
        },

        statusText() {
            return this.$t(`swag-paypal-method.merchantStatusText.${this.merchantStatus}`);
        },

        showMerchantInformation() {
            return this.merchantInformationStore.canPPCP;
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.fetchPaymentMethods();
        },

        async fetchPaymentMethods() {
            this.isLoadingPaymentMethods = true;

            const paymentMethods = await this.paymentMethodRepository.search(this.paymentMethodCriteria, Context.api)
                .catch(() => ([]));

            this.paymentMethods = paymentMethods
                .filter((pm) => {
                    if (pm.formattedHandlerIdentifier === 'handler_swag_pospayment') {
                        return false;
                    }

                    return !([
                        'handler_swag_trustlyapmhandler',
                        'handler_swag_giropayapmhandler',
                        'handler_swag_sofortapmhandler',
                    ].includes(pm.formattedHandlerIdentifier ?? '') && !pm.active);
                });

            this.isLoadingPaymentMethods = false;
        },

        async onUpdateActive(paymentMethod: TEntity<'payment_method'>, active: boolean) {
            paymentMethod.active = active;

            await this.paymentMethodRepository.save(paymentMethod, Context.api)
                .then(() => {
                    this.createNotificationSuccess({
                        message: this.$t(
                            `swag-paypal-method.switch.${paymentMethod.active ? 'active' : 'inactive'}`,
                            { name: paymentMethod.translated?.name || paymentMethod.name },
                        ),
                    });
                })
                .catch(() => { paymentMethod.active = !active; });
        },
    },
});
