import { mount } from '@vue/test-utils';
import SwSalesChannelDetail from 'src/module/sw-sales-channel/page/sw-sales-channel-detail';
import SwSalesChannelDetailExtension from '.';
import { PAYPAL_AGENT_COMMERCE_SALES_CHANNEL_TYPE_ID } from "SwagPayPal/constant/swag-paypal.constant";

Shopware.Component.register('sw-sales-channel-detail', Promise.resolve({
    ...SwSalesChannelDetail,
    template: '<div>stub</div>',
}));

Shopware.Component.extend('swag-paypal-agent-commerce-sales-channel-detail', 'sw-sales-channel-detail', Promise.resolve(SwSalesChannelDetailExtension));

async function createWrapper() {
    return mount(await Shopware.Component.build('swag-paypal-agent-commerce-sales-channel-detail') as typeof SwSalesChannelDetailExtension, {
        global: {
            provide: {
                exportTemplateService: {
                    getProductExportTemplateRegistry: () => { return {}; },
                },
            },
        },
    });
}

describe('sw-sales-channel-detail', () => {
    it('should be a Vue component', async () => {
        const wrapper = await createWrapper();

        expect(wrapper.vm).toBeTruthy();
    });

    it('should be agent commerce type', async () => {
        const wrapper = await createWrapper();

        wrapper.setData({
            salesChannel: {
                typeId: PAYPAL_AGENT_COMMERCE_SALES_CHANNEL_TYPE_ID,
            },
        });

        expect(wrapper.vm.isAgentCommerceType).toBeTruthy();
    });

    it('should not be agent commerce type', async () => {
        const wrapper = await createWrapper();

        wrapper.setData({
            salesChannel: {
                typeId: 'some-other-type-id',
            },
        });

        expect(wrapper.vm.isAgentCommerceType).toBeFalsy();
    });

    it('should be product comparison', async () => {
        const wrapper = await createWrapper();

        wrapper.setData({
            salesChannel: {
                typeId: Shopware.Defaults.productComparisonTypeId,
            },
        });

        expect(wrapper.vm.isProductComparison).toBeTruthy();
    });

    it('should not be product comparison', async () => {
        const wrapper = await createWrapper();

        wrapper.setData({
            salesChannel: {
                typeId: 'some-other-type-id',
            },
        });

        expect(wrapper.vm.isProductComparison).toBeFalsy();
    });
});
