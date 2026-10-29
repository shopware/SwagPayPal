import { test as base, expect } from '@shopware-ag/acceptance-test-suite';

// Point of sale: the Zettle sales channel type installed by the plugin. The en dash is part of the stored name.
const ZETTLE_SALES_CHANNEL_TYPE_NAME = 'Point of Sale \u2013 Zettle by PayPal';

export type OpenZettleSalesChannelTask = () => () => Promise<{
    salesChannelId: string
    webhookSigningKey: string
}>;

export const OpenZettleSalesChannel = base.extend<FixtureTypes>({
    OpenZettleSalesChannel: async ({ AdminApiContext, IdProvider, TestDataService }, use) => {
        const task: OpenZettleSalesChannelTask = () => {
            return async function OpenZettleSalesChannel() {
                const salesChannel = IdProvider.getIdPair();
                const posChannelId = IdProvider.getIdPair().uuid;
                const storefront = TestDataService.defaultSalesChannel;
                const name = `${TestDataService.namePrefix}SEC-09-POS-${salesChannel.id}${TestDataService.nameSuffix}`;
                const webhookSigningKey = IdProvider.getIdPair().id + IdProvider.getIdPair().id;
                const typeResponse = await AdminApiContext.post('search/sales-channel-type', {
                    data: {
                        limit: 1,
                        filter: [
                            {
                                type: 'equals',
                                field: 'name',
                                value: ZETTLE_SALES_CHANNEL_TYPE_NAME,
                            },
                        ],
                    },
                });
                expect(typeResponse.ok(), 'Should find the Zettle point-of-sale sales channel type').toBeTruthy();
                const typeJson = await typeResponse.json() as { data: { id: string }[] };
                const typeId = typeJson.data[0]?.id;
                if (!typeId) {
                    throw new Error(`Zettle point-of-sale sales channel type "${ZETTLE_SALES_CHANNEL_TYPE_NAME}" was not found.`);
                }

                const created = await AdminApiContext.post('sales-channel', {
                    data: {
                        id: salesChannel.uuid,
                        name,
                        typeId,
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
                TestDataService.addCreatedRecord('sales-channel', salesChannel.uuid);

                const posChannel = await AdminApiContext.post('swag-paypal-pos-sales-channel', {
                    data: {
                        id: posChannelId,
                        salesChannelId: salesChannel.uuid,
                        apiKey: 'sec-09-not-a-real-api-key',
                        webhookSigningKey,
                    },
                });
                expect(posChannel.ok()).toBeTruthy();
                // The sales channel delete cascades to this row. Deleting the row itself is restricted while the channel exists.
                return {
                    salesChannelId: salesChannel.uuid,
                    webhookSigningKey,
                };
            };
        };

        await use(task);
    },
});
