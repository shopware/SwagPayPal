import { test as base, expect } from '@shopware-ag/acceptance-test-suite';

const POS_SALES_CHANNEL_TYPE_ID = '1ce0868f406d47d98cfe4b281e62f099';

export type OpenZettleSalesChannelTask = () => () => Promise<{
    salesChannelId: string
    close: () => Promise<void>
}>;

export const OpenZettleSalesChannel = base.extend<FixtureTypes>({
    OpenZettleSalesChannel: async ({ AdminApiContext, IdProvider, TestDataService }, use) => {
        const task: OpenZettleSalesChannelTask = () => {
            return async function OpenZettleSalesChannel() {
                const salesChannelId = IdProvider.getIdPair().uuid;
                const posChannelId = IdProvider.getIdPair().uuid;
                const storefront = TestDataService.defaultSalesChannel;

                const created = await AdminApiContext.post('sales-channel', {
                    data: {
                        id: salesChannelId,
                        name: 'SEC-09 POS',
                        typeId: POS_SALES_CHANNEL_TYPE_ID,
                        accessKey: `SWSC${IdProvider.getIdPair().id}`.slice(0, 26),
                        active: false,
                        languageId: storefront.languageId,
                        currencyId: storefront.currencyId,
                        paymentMethodId: storefront.paymentMethodId,
                        shippingMethodId: storefront.shippingMethodId,
                        countryId: storefront.countryId,
                        customerGroupId: storefront.customerGroupId,
                        navigationCategoryId: storefront.navigationCategoryId,
                        currencies: [{ id: storefront.currencyId }],
                        languages: [{ id: storefront.languageId }],
                        countries: [{ id: storefront.countryId }],
                        paymentMethods: [{ id: storefront.paymentMethodId }],
                        shippingMethods: [{ id: storefront.shippingMethodId }],
                    },
                });
                expect(created.ok()).toBeTruthy();

                const posChannel = await AdminApiContext.post('swag-paypal-pos-sales-channel', {
                    data: {
                        id: posChannelId,
                        salesChannelId,
                        apiKey: 'sec-09-not-a-real-api-key',
                        webhookSigningKey: IdProvider.getIdPair().id + IdProvider.getIdPair().id,
                    },
                });
                expect(posChannel.ok()).toBeTruthy();

                return {
                    salesChannelId,
                    close: async () => {
                        await AdminApiContext.delete(`swag-paypal-pos-sales-channel/${posChannelId}`);
                        await AdminApiContext.delete(`sales-channel/${salesChannelId}`);
                    },
                };
            };
        };

        await use(task);
    },
});
