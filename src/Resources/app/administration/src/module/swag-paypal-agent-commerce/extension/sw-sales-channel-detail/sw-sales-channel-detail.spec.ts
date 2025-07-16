import { mount } from '@vue/test-utils';
import SwSalesChannelDetail from 'src/module/sw-sales-channel/page/sw-sales-channel-detail';
import SwSalesChannelDetailExtension from '.';

Shopware.Component.register('sw-sales-channel-detail', Promise.resolve({
    ...SwSalesChannelDetail,
    template: '<div>stub</div>',
}));

Shopware.Component.extend('swag-paypal-agent-commerce-sales-channel-detail', 'sw-sales-channel-detail', Promise.resolve(SwSalesChannelDetailExtension));

async function createWrapper() {
    return mount(await Shopware.Component.build('swag-paypal-agent-commerce-sales-channel-detail') as typeof SwSalesChannelDetailExtension);
}

describe('sw-sales-channel-detail', () => {
    it('should be a Vue component', async () => {
        const wrapper = await createWrapper();

        expect(wrapper.vm).toBeTruthy();
    });

    it('should be product comparison', async () => {
        const wrapper = await createWrapper();

        expect(wrapper.vm.isProductComparison).toBeTruthy();
    });
});
