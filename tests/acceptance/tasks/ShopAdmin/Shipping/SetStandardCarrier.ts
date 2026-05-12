import { test as base, expect } from '@shopware-ag/acceptance-test-suite';

export type SetStandardCarrierTask = Task<[name: string]>;

export const SetStandardCarrier = base.extend<FixtureTypes>({
    SetStandardCarrier: async ({
        AdminShippingDetail,
    }, use) => {
        const task: SetStandardCarrierTask = (name) => {
            return async function SetStandardCarrier() {
                await expect(AdminShippingDetail.standardShippingCarrierCard).toBeVisible();
                await AdminShippingDetail.standardShippingCarrierList.click();
                await AdminShippingDetail.standardShippingCarrierInput.fill(name);
                await AdminShippingDetail.getCarrierListResult(name).click();
            };
        };

        await use(task);
    },
});
