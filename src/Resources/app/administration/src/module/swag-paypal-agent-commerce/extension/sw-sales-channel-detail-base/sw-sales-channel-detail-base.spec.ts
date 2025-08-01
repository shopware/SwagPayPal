import { mount } from '@vue/test-utils';
import SwSalesChannelDetailBase from 'src/module/sw-sales-channel/view/sw-sales-channel-detail-base';
import SwSalesChannelDetailBaseExtension from '.';
import { PAYPAL_AGENT_COMMERCE_SALES_CHANNEL_TYPE_ID } from "SwagPayPal/constant/swag-paypal.constant";

Shopware.Component.register('sw-sales-channel-detail-base', Promise.resolve({
    ...SwSalesChannelDetailBase,
    template: '<div>stub</div>',
}));

Shopware.Component.extend('swag-paypal-agent-commerce-sales-channel-detail-base', 'sw-sales-channel-detail-base', Promise.resolve(SwSalesChannelDetailBaseExtension));

async function createWrapper() {
    return mount(await Shopware.Component.build('swag-paypal-agent-commerce-sales-channel-detail-base') as typeof SwSalesChannelDetailBaseExtension, {
        props: {
            salesChannel: {},
            productExport: {},
            customFieldSets: [],
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
            },
        },
    });
}

describe('sw-sales-channel-detail-base', () => {
    it('should be a Vue component', async () => {
        const wrapper = await createWrapper();

        expect(wrapper.vm).toBeTruthy();
    });

    it('should be agent commerce type', async () => {
        const wrapper = await createWrapper();

        await wrapper.setProps({
            salesChannel: {
                typeId: PAYPAL_AGENT_COMMERCE_SALES_CHANNEL_TYPE_ID,
            },
        });

        expect(wrapper.vm.isAgentCommerceType).toBeTruthy();
    });

    it('should not be agent commerce type', async () => {
        const wrapper = await createWrapper();

        await wrapper.setProps({
            salesChannel: {
                typeId: 'some-other-type-id',
            },
        });

        expect(wrapper.vm.isAgentCommerceType).toBeFalsy();
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
});
