import template from './swag-paypal-settings-sales-channel-switch.html.twig';
import './swag-paypal-settings-sales-channel-switch.scss';

const { Defaults } = Shopware;
const { Criteria } = Shopware.Data;

type SalesChannel = {
    value: string | null;
    label: string;
};

/**
 * @private
 */
export default Shopware.Component.wrapComponentConfig({
    template,

    inject: [
        'acl',
        'repositoryFactory',
        'SwagPaypalPaymentMethodService',
    ],

    data(): {
        isLoading: boolean;
        salesChannels: SalesChannel[];
        defaultPaymentMethods: 'none' | 'loading' | 'success';
    } {
        return {
            isLoading: true,
            salesChannels: [],
            defaultPaymentMethods: 'none',
        };
    },

    computed: {
        settingsStore() {
            return Shopware.Store.get('swagPayPalSettings');
        },

        salesChannelRepository() {
            return this.repositoryFactory.create('sales_channel');
        },

        salesChannelCriteria(): TCriteria {
            const criteria = new Criteria(1, 500);

            criteria.addFilter(Criteria.equalsAny('typeId', [
                Defaults.storefrontSalesChannelTypeId,
                Defaults.apiSalesChannelTypeId,
            ]));

            return criteria;
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.fetchSalesChannels();
        },

        async fetchSalesChannels() {
            try {
                const salesChannels = await this.salesChannelRepository.search(this.salesChannelCriteria, Shopware.Context.api);

                this.salesChannels = [{
                    value: null,
                    label: this.$t('sw-sales-channel-switch.labelDefaultOption'),
                }];

                salesChannels.forEach((salesChannel) => {
                    this.salesChannels.push({
                        value: salesChannel.id,
                        label: salesChannel.translated?.name || salesChannel.name,
                    });
                });
            } finally {
                this.isLoading = false;
            }
        },

        onSetPaymentMethodDefault() {
            this.defaultPaymentMethods = 'loading';

            this.SwagPaypalPaymentMethodService.setDefaultPaymentForSalesChannel(this.settingsStore.salesChannel)
                .then(() => { this.defaultPaymentMethods = 'success'; })
                .catch(() => { this.defaultPaymentMethods = 'none'; });
        },
    },
});
