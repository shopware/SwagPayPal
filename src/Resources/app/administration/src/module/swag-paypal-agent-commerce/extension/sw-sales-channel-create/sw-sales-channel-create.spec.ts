import { mount } from '@vue/test-utils';
import SwSalesChannelCreate from 'src/module/sw-sales-channel/page/sw-sales-channel-create';
import SwSalesChannelCreateExtension from '.';

Shopware.Component.register('sw-sales-channel-create', Promise.resolve({
    ...SwSalesChannelCreate,
    template: '<div>stub</div>',
}));

Shopware.Component.extend('swag-paypal-agent-commerce-sales-channel-create', 'sw-sales-channel-create', Promise.resolve(SwSalesChannelCreateExtension));

async function createWrapper() {
    return mount(await Shopware.Component.build('swag-paypal-agent-commerce-sales-channel-create') as typeof SwSalesChannelCreateExtension);
}

describe('sw-sales-channel-create', () => {
    it('should be a Vue component', async () => {
        const wrapper = await createWrapper();

        expect(wrapper.vm).toBeTruthy();
    });

    it('should be product comparison', async () => {
        const wrapper = await createWrapper();

        expect(wrapper.vm.isProductComparison).toBeTruthy();
    });
});
