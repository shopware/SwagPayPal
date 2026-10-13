import { test as base, expect } from '@shopware-ag/acceptance-test-suite';
import type { PayPalTestDataService } from '@services/PayPalTestDataService';

const POS_SALES_CHANNEL_TYPE_ID = '1ce0868f406d47d98cfe4b281e62f099';

export type OpenZettleSalesChannelTask = () => () => Promise<{
    salesChannelId: string
}>;

export const OpenZettleSalesChannel = base.extend<FixtureTypes>({
    OpenZettleSalesChannel: async ({ AdminApiContext, IdProvider, TestDataService }, use) => {
        const task: OpenZettleSalesChannelTask = () => {
            return async function OpenZettleSalesChannel() {
                const salesChannel = IdProvider.getIdPair();
                const posChannelId = IdProvider.getIdPair().uuid;
                const storefront = TestDataService.defaultSalesChannel;
                const paypalData = TestDataService as PayPalTestDataService;
                const name = `${TestDataService.namePrefix}SEC-09 POS-${salesChannel.id}${TestDataService.nameSuffix}`;

                const created = await AdminApiContext.post('sales-channel', {
                    data: {
                        id: salesChannel.uuid,
                        name,
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
                TestDataService.addCreatedRecord('sales-channel', salesChannel.uuid);

                const posChannel = await AdminApiContext.post('swag-paypal-pos-sales-channel', {
                    data: {
                        id: posChannelId,
                        salesChannelId: salesChannel.uuid,
                        apiKey: 'sec-09-not-a-real-api-key',
                        webhookSigningKey: IdProvider.getIdPair().id + IdProvider.getIdPair().id,
                    },
                });
                expect(posChannel.ok()).toBeTruthy();
                // Deleted before the sales channel. The POS row restricts that delete.
                paypalData.addPayPalCreatedRecord('swag_paypal_pos_sales_channel', posChannelId);

                return {
                    salesChannelId: salesChannel.uuid,
                };
            };
        };

        await use(task);
    },
});
