import { test as base } from '@playwright/test';

type FillPaymentACDCOptions = {
    holderName?: string
    number?: string
    experationDate?: string
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
                if (options.experationDate) {
                    await StorefrontCheckoutConfirm.page.getByPlaceholder('Expiration date').fill(options.experationDate);
                }
                if (options.cvv) {
                    await StorefrontCheckoutConfirm.page.getByPlaceholder('Security code').fill(options.cvv);
                }
            };
        };

        await use(task);
    },
});
