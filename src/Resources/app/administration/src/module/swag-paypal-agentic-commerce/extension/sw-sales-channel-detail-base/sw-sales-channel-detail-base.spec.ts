import { mount } from '@vue/test-utils';
import SwSalesChannelDetailBase from 'src/module/sw-sales-channel/view/sw-sales-channel-detail-base';
import SwSalesChannelDetailBaseExtension from '.';
import { PAYPAL_AGENTIC_COMMERCE_SALES_CHANNEL_TYPE_ID } from "SwagPayPal/constant/swag-paypal.constant";

const { Criteria } = Shopware.Data;

Shopware.Component.register('sw-sales-channel-detail-base', Promise.resolve({
    ...SwSalesChannelDetailBase,
    template: '<div>stub</div>',
}));

Shopware.Component.extend('swag-paypal-agentic-commerce-sales-channel-detail-base', 'sw-sales-channel-detail-base', Promise.resolve(SwSalesChannelDetailBaseExtension));

async function createWrapper(props = {}) {
    return mount(await Shopware.Component.build('swag-paypal-agentic-commerce-sales-channel-detail-base') as typeof SwSalesChannelDetailBaseExtension, {
        props: {
            salesChannel: {},
            productExport: {},
            customFieldSets: [],
            ...props,
        },
        global: {
            provide: {
                salesChannelService: {
                    generateKey: () => { return new Promise((resolve) => resolve('generated-key')); },
                },
                productExportService: {},
                knownIpsService: {
                    getKnownIps: () => Promise.resolve(),
                },
                repositoryFactory: {
                    create: () => ({
                        search: () => {
                            return Promise.resolve([]);
                        },
                        get: () => {
                            return Promise.resolve();
                        },
                        delete: () => {
                            return Promise.resolve();
                        },
                    }),
                },
                systemConfigApiService: { getValues: () => Promise.resolve([]) },
                SwagPayPalHoneyWebhookService: {
                    register: jest.fn(() => Promise.resolve()),
                },
            },
        },
    });
}

describe('sw-sales-channel-detail-base', () => {
    it('should be a Vue component', async () => {
        const wrapper = await createWrapper();

        expect(wrapper.vm).toBeTruthy();
    });

    it('should be agentic commerce type', async () => {
        const wrapper = await createWrapper();

        await wrapper.setProps({
            salesChannel: {
                typeId: PAYPAL_AGENTIC_COMMERCE_SALES_CHANNEL_TYPE_ID,
            },
        });

        expect(wrapper.vm.isAgenticCommerceType).toBeTruthy();
    });

    it('should not be agentic commerce type', async () => {
        const wrapper = await createWrapper();

        await wrapper.setProps({
            salesChannel: {
                typeId: 'some-other-type-id',
            },
        });

        expect(wrapper.vm.isAgenticCommerceType).toBeFalsy();
    });

    it('should be product comparison', async () => {
        const wrapper = await createWrapper();

        await wrapper.setProps({
            salesChannel: {
                typeId: Shopware.Defaults.productComparisonTypeId,
            },
        });

        expect(wrapper.vm.isProductComparison).toBeTruthy();
    });

    it('should not be product comparison', async () => {
        const wrapper = await createWrapper();

        await wrapper.setProps({
            salesChannel: {
                typeId: 'some-other-type-id',
            },
        });

        expect(wrapper.vm.isProductComparison).toBeFalsy();
    });

    it('should restrict agentic storefront sales channel criteria to US storefronts', async () => {
        const storefrontSalesChannelCriteria = new Criteria(1, 25);
        storefrontSalesChannelCriteria.addFilter(Criteria.equals('active', true));

        const wrapper = await createWrapper({ storefrontSalesChannelCriteria });

        expect(wrapper.vm.agenticStorefrontSalesChannelCriteria).not.toBe(storefrontSalesChannelCriteria);
        expect(wrapper.vm.agenticStorefrontSalesChannelCriteria.filters).toEqual([
            Criteria.equals('active', true),
            Criteria.equals('country.iso3', 'USA'),
        ]);
        expect(storefrontSalesChannelCriteria.filters).toEqual([
            Criteria.equals('active', true),
        ]);
    });
});
