import { mount } from '@vue/test-utils';
import SwagPayPalSettingsSalesChannelSwitch from '.';

Shopware.Component.register('swag-paypal-settings-sales-channel-switch', Promise.resolve(SwagPayPalSettingsSalesChannelSwitch));

async function createWrapper() {
    return mount(
        await Shopware.Component.build('swag-paypal-settings-sales-channel-switch') as typeof SwagPayPalSettingsSalesChannelSwitch,
        {
            global: {
                mocks: { $t: (key: string) => key },
                provide: {
                    acl: { can: () => true },
                    repositoryFactory: {
                        create: () => ({
                            search: jest.fn(() => Promise.resolve([
                                { id: 'id-1', name: 'Name 1' },
                                { id: 'id-2', name: 'Name 2' },
                            ])),
                        }),
                    },
                    SwagPaypalPaymentMethodService: {
                        setDefaultPaymentForSalesChannel: jest.fn(() => Promise.resolve()),
                    },
                },
                stubs: {
                    'sw-card': await wrapTestComponent('sw-card', { sync: true }),
                    'sw-card-deprecated': await wrapTestComponent('sw-card-deprecated', { sync: true }),
                    'sw-container': await wrapTestComponent('sw-container', { sync: true }),
                    'sw-internal-link': await wrapTestComponent('sw-internal-link', { sync: true }),
                    'sw-button-process': await wrapTestComponent('sw-button-process', { sync: true }),
                    'sw-single-select': await wrapTestComponent('sw-single-select', { sync: true }),
                },
            },
        },
    );
}

describe('swag-paypal-settings-sales-channel-switch', () => {
    it('should be a Vue.js component', async () => {
        const wrapper = await createWrapper();

        expect(wrapper.vm).toBeTruthy();
    });

    it('should fetch sales channels on creation', async () => {
        const wrapper = await createWrapper();

        expect(wrapper.vm.salesChannelRepository.search).toHaveBeenCalledWith(
            wrapper.vm.salesChannelCriteria,
            Shopware.Context.api,
        );
        expect(wrapper.vm.salesChannels).toEqual([
            { value: null, label: 'sw-sales-channel-switch.labelDefaultOption' },
            { value: 'id-1', label: 'Name 1' },
            { value: 'id-2', label: 'Name 2' },
        ]);
    });

    it('should select a different sales channel', async () => {
        const wrapper = await createWrapper();

        expect(wrapper.vm.settingsStore.salesChannel).toBeNull();

        const select = wrapper.findComponent<VueComponent>('.sw-single-select');
        expect(select.exists()).toBe(true);
        // eslint-disable-next-line @typescript-eslint/no-unsafe-call
        select.vm.$emit('update:value', 'id-2');

        expect(wrapper.vm.settingsStore.salesChannel).toBe('id-2');
    });

    it('should set payment method as default', async () => {
        const wrapper = await createWrapper();

        const button = wrapper.findComponent<VueComponent>('.sw-button-process');
        expect(button.exists()).toBe(true);
        // eslint-disable-next-line @typescript-eslint/no-unsafe-call
        button.vm.$emit('click');

        expect(wrapper.vm.defaultPaymentMethods).toBe('loading');
        expect(wrapper.vm.SwagPaypalPaymentMethodService.setDefaultPaymentForSalesChannel).toHaveBeenCalledWith(null);
        await wrapper.vm.$nextTick();
        expect(wrapper.vm.defaultPaymentMethods).toBe('success');
    });

    it('should have link to payment method', async () => {
        const wrapper = await createWrapper();

        const link = wrapper.findComponent<VueComponent>('.sw-internal-link');
        expect(link.exists()).toBe(true);
        expect(link.vm.routerLink).toEqual({ name: 'sw.settings.payment.overview' });
    });
});
