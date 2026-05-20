import EntityCollection from '@shopware-ag/meteor-admin-sdk/es/_internals/data/EntityCollection';
import SwSalesChannelModalGridExtension from '.';
import { PAYPAL_AGENTIC_COMMERCE_SALES_CHANNEL_TYPE_ID } from '../../../constant/swag-paypal.constant';

const { Criteria } = Shopware.Data;

type SalesChannelModalGridTestContext = {
    $super: jest.Mock;
    salesChannelRepository: Pick<TRepository<'sales_channel'>, 'searchIds'>;
    usDefaultCountryStorefrontCriteria: () => TCriteria;
    hasUsDefaultCountryStorefrontSalesChannel: (context?: Parameters<TRepository<'sales_channel'>['searchIds']>[1]) => Promise<boolean>;
    withPayPalAgenticCommerceFilter: (
        repository: TRepository<'sales_channel_type'>,
    ) => TRepository<'sales_channel_type'>;
};

type SalesChannelModalGridMethods = Omit<SalesChannelModalGridTestContext, '$super' | 'salesChannelRepository'>;

type SalesChannelModalGridComputed = {
    salesChannelTypeRepository: (this: SalesChannelModalGridTestContext) => TRepository<'sales_channel_type'>;
};

const componentMethods = SwSalesChannelModalGridExtension.methods as SalesChannelModalGridMethods;
const componentComputed = SwSalesChannelModalGridExtension.computed as SalesChannelModalGridComputed;

function createSalesChannelType(id: string): TEntity<'sales_channel_type'> {
    return {
        id,
        translated: {
            name: id,
            description: id,
        },
    } as TEntity<'sales_channel_type'>;
}

function createSalesChannelTypes(): TEntityCollection<'sales_channel_type'> {
    return new EntityCollection<'sales_channel_type'>(
        '/sales-channel-type',
        'sales_channel_type',
        Shopware.Context.api,
        null,
        [
            createSalesChannelType(Shopware.Defaults.storefrontSalesChannelTypeId),
            createSalesChannelType(Shopware.Defaults.agenticCommerceTypeId),
            createSalesChannelType(PAYPAL_AGENTIC_COMMERCE_SALES_CHANNEL_TYPE_ID),
        ],
        3,
    );
}

function createComponent(hasUsDefaultCountryStorefrontSalesChannel: boolean) {
    const salesChannelTypes = createSalesChannelTypes();
    const salesChannelTypeSearch = jest.fn().mockResolvedValue(salesChannelTypes);
    const salesChannelTypeRepository = {
        search: salesChannelTypeSearch,
    } as Pick<TRepository<'sales_channel_type'>, 'search'>;
    const salesChannelSearchIds = jest.fn().mockResolvedValue({
        data: hasUsDefaultCountryStorefrontSalesChannel ? ['us-storefront-sales-channel-id'] : [],
        total: hasUsDefaultCountryStorefrontSalesChannel ? 1 : 0,
    });

    return {
        component: {
            $super: jest.fn(() => salesChannelTypeRepository),
            salesChannelRepository: {
                searchIds: salesChannelSearchIds,
            },
            usDefaultCountryStorefrontCriteria: componentMethods.usDefaultCountryStorefrontCriteria,
            hasUsDefaultCountryStorefrontSalesChannel: componentMethods.hasUsDefaultCountryStorefrontSalesChannel,
            withPayPalAgenticCommerceFilter: componentMethods.withPayPalAgenticCommerceFilter,
        } as SalesChannelModalGridTestContext,
        salesChannelTypeRepository,
        salesChannelTypeSearch,
        salesChannelSearchIds,
    };
}

describe('sw-sales-channel-modal-grid', () => {
    it('should decorate the original sales channel type repository and show PayPal Agentic Commerce when a USA storefront exists', async () => {
        const { component, salesChannelTypeRepository, salesChannelTypeSearch } = createComponent(true);

        const repository = componentComputed.salesChannelTypeRepository.call(component);
        const criteria = new Criteria(1, 500);
        const result = await repository.search(criteria, Shopware.Context.api);

        expect(component.$super).toHaveBeenCalledWith('salesChannelTypeRepository');
        expect(repository).toBe(salesChannelTypeRepository);
        expect(repository.search).not.toBe(salesChannelTypeSearch);
        expect(salesChannelTypeSearch).toHaveBeenCalledWith(criteria, Shopware.Context.api);
        expect(salesChannelTypeSearch.mock.contexts[0]).toBe(salesChannelTypeRepository);
        expect(result.has(PAYPAL_AGENTIC_COMMERCE_SALES_CHANNEL_TYPE_ID)).toBe(true);
        expect(result.has(Shopware.Defaults.agenticCommerceTypeId)).toBe(true);
        expect(result.total).toBe(3);
    });

    it('should add one filter for PayPal Agentic Commerce without a storefront with USA as default country', async () => {
        const { component, salesChannelTypeSearch } = createComponent(false);

        const repository = componentComputed.salesChannelTypeRepository.call(component);
        const criteria = new Criteria(1, 500);

        await repository.search(criteria, Shopware.Context.api);

        const filteredCriteria = salesChannelTypeSearch.mock.calls[0][0] as TCriteria;

        expect(filteredCriteria).not.toBe(criteria);
        expect(criteria.filters).toEqual([]);
        expect(filteredCriteria.filters).toEqual([
            Criteria.not('AND', [
                Criteria.equals('id', PAYPAL_AGENTIC_COMMERCE_SALES_CHANNEL_TYPE_ID),
            ]),
        ]);
    });

    it('should look for storefronts with USA as default country', async () => {
        const { component, salesChannelSearchIds } = createComponent(true);

        const repository = componentComputed.salesChannelTypeRepository.call(component);

        await repository.search(new Criteria(1, 500), Shopware.Context.api);

        const criteria = salesChannelSearchIds.mock.calls[0][0] as TCriteria;

        expect(criteria.filters).toEqual([
            Criteria.equals('typeId', Shopware.Defaults.storefrontSalesChannelTypeId),
            Criteria.equals('country.iso3', 'USA'),
        ]);
    });
});
