import { test as base } from '@playwright/test';
import { PayPalDataProvider } from './PayPalDataProvider';

export interface ServicesTypes {
    PayPalDataProvider: PayPalDataProvider
}

export const test = base.extend<FixtureTypes>({
    PayPalDataProvider: async ({}, use) => {
        await use(new PayPalDataProvider());
    },
});
