import { test as ShopwareTestSuite, mergeTests } from '@shopware-ag/acceptance-test-suite';
import { test as shopCustomerTasks } from '@tasks/ShopCustomerTasks';
import { test as shopAdminTasks } from '@tasks/ShopAdminTasks';
import { test as adminPages } from '@page-objects/AdministrationPages';
import { test as storefrontPages } from '@page-objects/StorefrontPages';
import { test as services } from '@services/Services';
import { test as payPalTestDataService } from '../fixtures/PayPalTestData';
import type { FixtureTypes as BaseTypes } from '@shopware-ag/acceptance-test-suite';
import type { AdminPageTypes } from '../page-objects/AdministrationPages';
import type { StorefrontPageTypes } from '../page-objects/StorefrontPages';
import type { DataFixtureTypes } from '../data-fixtures/DataFixtures';

export * from '@shopware-ag/acceptance-test-suite';

export type FixtureTypes = AdminPageTypes & StorefrontPageTypes & DataFixtureTypes & BaseTypes;

export const test = mergeTests(
    ShopwareTestSuite,
    shopCustomerTasks,
    shopAdminTasks,
    adminPages,
    storefrontPages,
    services,
    payPalTestDataService,
);
