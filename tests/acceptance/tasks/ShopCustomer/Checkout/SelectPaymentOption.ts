import { test as base } from '@playwright/test';

type PaymentKeys<T extends string> = T extends `payment${infer U}` ? U : never;

export type SelectPaymentOptionTask = Task<[name: PaymentKeys<keyof FixtureTypes['StorefrontCheckoutConfirm']>]>;

export const SelectPaymentOption = base.extend<FixtureTypes>({
    SelectPaymentOption: async ({ ShopCustomer, StorefrontCheckoutConfirm }, use) => {
        const task: SelectPaymentOptionTask = (name) => {
            return async function SelectPaymentOption() {
                await StorefrontCheckoutConfirm[`payment${name}`].check();
                await ShopCustomer.expects(StorefrontCheckoutConfirm[`payment${name}`]).toBeChecked();
            };
        };

        await use(task);
    },
});
