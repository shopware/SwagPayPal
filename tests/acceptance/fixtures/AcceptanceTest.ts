import { test as ShopwareTestSuite, mergeTests } from '@shopware-ag/acceptance-test-suite';
import { test as paypalShopCustomerTasks } from '@tasks/ShopCustomerTasks';
import { test as paypalShopAdminTasks } from '@tasks/ShopAdminTasks';
import { test as paypalAdminPages } from '@page-objects/AdministrationPages';
import { test as paypalStorefrontPages } from '@page-objects/StorefrontPages';
import { test as services } from '@services/Services';
import { test as payPalTestDataService } from '../fixtures/PayPalTestData';

export * from '@shopware-ag/acceptance-test-suite';

export const test = mergeTests(
    ShopwareTestSuite,
    paypalShopCustomerTasks,
    paypalShopAdminTasks,
    paypalAdminPages,
    paypalStorefrontPages,
    services,
    payPalTestDataService,
);
