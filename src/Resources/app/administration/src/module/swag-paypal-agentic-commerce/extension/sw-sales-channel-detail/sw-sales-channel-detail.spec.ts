import { mount } from '@vue/test-utils';
import SwSalesChannelDetail from 'src/module/sw-sales-channel/page/sw-sales-channel-detail';
import SwSalesChannelDetailExtension from '.';
import { PAYPAL_AGENTIC_COMMERCE_SALES_CHANNEL_TYPE_ID } from "SwagPayPal/constant/swag-paypal.constant";

Shopware.Component.register('sw-sales-channel-detail', Promise.resolve({
    ...SwSalesChannelDetail,
    template: '<div>stub</div>',
}));

Shopware.Component.extend('swag-paypal-agentic-commerce-sales-channel-detail', 'sw-sales-channel-detail', Promise.resolve(SwSalesChannelDetailExtension));

async function createWrapper() {
    return mount(await Shopware.Component.build('swag-paypal-agentic-commerce-sales-channel-detail') as typeof SwSalesChannelDetailExtension, {
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

    it('should be agentic commerce type', async () => {
        const wrapper = await createWrapper();

        wrapper.setData({
            salesChannel: {
                typeId: PAYPAL_AGENTIC_COMMERCE_SALES_CHANNEL_TYPE_ID,
            },
        });

        expect(wrapper.vm.isAgenticCommerceType).toBeTruthy();
    });

    it('should not be agentic commerce type', async () => {
        const wrapper = await createWrapper();

        wrapper.setData({
            salesChannel: {
                typeId: 'some-other-type-id',
            },
        });

        expect(wrapper.vm.isAgenticCommerceType).toBeFalsy();
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
