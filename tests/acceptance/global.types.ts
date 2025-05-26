import type { FixtureTypes as BaseTypes } from '@shopware-ag/acceptance-test-suite';
import type { AdminPageTypes } from '@page-objects/AdministrationPages';
import type { StorefrontPageTypes } from '@page-objects/StorefrontPages';
import type { DataFixtureTypes } from 'data-fixtures/DataFixtures';
import type { ShopAdminTasks } from '@tasks/ShopAdminTasks';
import type { ShopCustomerTasks } from '@tasks/ShopCustomerTasks';
import type { ServicesTypes } from '@services/Services';

interface TestFixtureTypes extends AdminPageTypes, StorefrontPageTypes, DataFixtureTypes, ShopAdminTasks, ShopCustomerTasks, ServicesTypes {
}

declare global {
    type Task<Args extends Array<unknown>> = (...args: Args) => () => Promise<void>;

    interface FixtureTypes extends Omit<BaseTypes, keyof TestFixtureTypes>, TestFixtureTypes {
    }
}
