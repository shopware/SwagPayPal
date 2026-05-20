import template from './sw-sales-channel-modal-grid.html.twig';
import './sw-sales-channel-modal-grid.scss';
import {
    PAYPAL_AGENTIC_COMMERCE_SALES_CHANNEL_TYPE_ID,
    PAYPAL_POS_SALES_CHANNEL_TYPE_ID,
} from '../../../constant/swag-paypal.constant';

const { Criteria } = Shopware.Data;
const { Defaults } = Shopware;

type SalesChannelSearchContext = Parameters<TRepository<'sales_channel'>['searchIds']>[1];
type SalesChannelTypeSearchContext = Parameters<TRepository<'sales_channel_type'>['search']>[1];

type SalesChannelModalGrid = {
    $super: (name: string) => unknown;
    repositoryFactory: {
        create: (entityName: 'sales_channel') => TRepository<'sales_channel'>;
    };
    salesChannelRepository: TRepository<'sales_channel'>;
    salesChannelTypeRepository: TRepository<'sales_channel_type'>;
    salesChannelTypes: TEntityCollection<'sales_channel_type'>;
    hasUsDefaultCountryStorefrontSalesChannel: (context?: SalesChannelSearchContext) => Promise<boolean>;
    usDefaultCountryStorefrontCriteria: () => TCriteria;
    withPayPalAgenticCommerceFilter: (
        repository: TRepository<'sales_channel_type'>,
    ) => TRepository<'sales_channel_type'>;
};

export default Shopware.Component.wrapComponentConfig<SalesChannelModalGrid>({
    template,

    computed: {
        assetFilter() {
            return Shopware.Filter.getByName('asset');
        },

        salesChannelTypeRepository(): TRepository<'sales_channel_type'> {
            const repository = this.$super('salesChannelTypeRepository') as TRepository<'sales_channel_type'>;

            return this.withPayPalAgenticCommerceFilter(repository);
        },

        salesChannelRepository(): TRepository<'sales_channel'> {
            return this.repositoryFactory.create('sales_channel');
        },
    },

    methods: {
        isPayPalPosSalesChannel(salesChannelTypeId: string): boolean {
            const salesChannelType = this.salesChannelTypes.find(type => type.id === salesChannelTypeId);

            return salesChannelType?.id === PAYPAL_POS_SALES_CHANNEL_TYPE_ID;
        },

        usDefaultCountryStorefrontCriteria(): TCriteria {
            const criteria = new Criteria(1, 1);

            criteria.addFilter(Criteria.equals('typeId', Defaults.storefrontSalesChannelTypeId));
            criteria.addFilter(Criteria.equals('country.iso3', 'USA'));

            return criteria;
        },

        async hasUsDefaultCountryStorefrontSalesChannel(context?: SalesChannelSearchContext): Promise<boolean> {
            const usDefaultCountryStorefrontSalesChannels = await this.salesChannelRepository.searchIds(
                this.usDefaultCountryStorefrontCriteria(),
                context,
            );

            return usDefaultCountryStorefrontSalesChannels.total > 0;
        },

        withPayPalAgenticCommerceFilter(
            repository: TRepository<'sales_channel_type'>,
        ): TRepository<'sales_channel_type'> {
            const original = repository.search.bind(repository);

            repository.search = async (
                criteria: TCriteria,
                context?: SalesChannelTypeSearchContext,
                ...args: []
            ) => {
                if (await this.hasUsDefaultCountryStorefrontSalesChannel(context)) {
                    return original(criteria, context, ...args);
                }

                const filteredCriteria = Criteria.fromCriteria(criteria);

                // If no US sales channel exists, we will filter the agentic type
                filteredCriteria.addFilter(Criteria.not('AND', [
                    Criteria.equals('id', PAYPAL_AGENTIC_COMMERCE_SALES_CHANNEL_TYPE_ID),
                ]));

                return original(filteredCriteria, context, ...args);
            };

            return repository;
        },
    },
});
