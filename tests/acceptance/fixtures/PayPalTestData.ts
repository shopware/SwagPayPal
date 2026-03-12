import { test as base } from '@playwright/test';
import { PayPalTestDataService } from '../services/PayPalTestDataService';
import { FixtureTypes } from './AcceptanceTest';

export interface CommercialTestDataFixtureTypes {
    TestDataService: PayPalTestDataService
}

export const test = base.extend<FixtureTypes & CommercialTestDataFixtureTypes>({

    TestDataService: async ({ AdminApiContext, IdProvider, DefaultSalesChannel, SalesChannelBaseConfig }, use) => {
        const DataService = new PayPalTestDataService(AdminApiContext, IdProvider, {
            defaultSalesChannel: DefaultSalesChannel.salesChannel,
            defaultTaxId: SalesChannelBaseConfig.taxId,
            defaultCurrencyId: SalesChannelBaseConfig.defaultCurrencyId,
            defaultCategoryId: DefaultSalesChannel.salesChannel.navigationCategoryId,
            defaultLanguageId: DefaultSalesChannel.salesChannel.languageId,
            defaultCountryId: DefaultSalesChannel.salesChannel.countryId,
            defaultCustomerGroupId: DefaultSalesChannel.salesChannel.customerGroupId,
        });

        await use(DataService);

        // PayPal specific cleanup
        await DataService.cleanUpPayPalEntities();

        // General cleanup
        await DataService.cleanUp();
    },
});
