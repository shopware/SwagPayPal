import { test as base, expect } from '@shopware-ag/acceptance-test-suite';

export type ReadProductStockTask = (productId: string) => () => Promise<number>;

export const ReadProductStock = base.extend<FixtureTypes>({
    ReadProductStock: async ({ AdminApiContext }, use) => {
        const task: ReadProductStockTask = (productId) => {
            return async function ReadProductStock() {
                const response = await AdminApiContext.get(`product/${productId}`);

                expect(response.ok()).toBeTruthy();

                const stored = await response.json() as { data: { stock: number } };

                return stored.data.stock;
            };
        };

        await use(task);
    },
});
