import { test as ShopwareTestSuite, mergeTests } from '@shopware-ag/acceptance-test-suite';
import { test as shopCustomerTasks } from '@tasks/ShopCustomerTasks';
import { test as shopAdminTasks } from '@tasks/ShopAdminTasks';
import { test as adminPages } from '@page-objects/AdministrationPages';
import { test as storefrontPages } from '@page-objects/StorefrontPages';

export const test = mergeTests(
    ShopwareTestSuite,
    shopCustomerTasks,
    shopAdminTasks,
    adminPages,
    storefrontPages,
);
