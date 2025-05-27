import { test as base } from '@playwright/test';

type FillPaymentACDCOptions = {
    holderName?: string
    number?: string
    expirationDate?: string
    cvv?: string
};

export type FillPaymentACDCTask = Task<[options: FillPaymentACDCOptions]>;

export const FillPaymentACDC = base.extend<FixtureTypes>({
    FillPaymentACDC: async ({ StorefrontCheckoutConfirm }, use) => {
        const task: FillPaymentACDCTask = (options) => {
            return async function SelectInvoicePaymentOption() {
                if (options.holderName) {
                    await StorefrontCheckoutConfirm.page.getByPlaceholder('Card holder name').fill(options.holderName);
                }
                if (options.number) {
                    await StorefrontCheckoutConfirm.page.getByPlaceholder('Card number').fill(options.number);
                }
                if (options.expirationDate) {
                    await StorefrontCheckoutConfirm.page.getByPlaceholder('Expiration date').fill(options.expirationDate);
                }
                if (options.cvv) {
                    await StorefrontCheckoutConfirm.page.getByPlaceholder('Security code').fill(options.cvv);
                }
            };
        };

        await use(task);
    },
});
