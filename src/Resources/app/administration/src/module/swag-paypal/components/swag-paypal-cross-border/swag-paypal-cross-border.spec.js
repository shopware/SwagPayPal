import { mount } from '@vue/test-utils';

Shopware.Component.register('swag-paypal-cross-border', () => import('.'));

async function createWrapper(customOptions = {}) {
    const options = {
        global: {
            mocks: { $tc: (key) => key, $t: (key) => key },
            provide: {
                acl: { can: () => true },
            },
            stubs: {
                'sw-card': await wrapTestComponent('sw-card', { sync: true }),
                'sw-card-deprecated': await wrapTestComponent('sw-card-deprecated', { sync: true }),
            },
        },
        props: {
            actualConfigData: {
                crossBorderMessagingEnabled: true,
                crossBorderBuyerCountry: null,
            },
            allConfigs: {
                null: {
                    crossBorderMessagingEnabled: true,
                    crossBorderBuyerCountry: null,
                },
            },
            selectedSalesChannelId: 'null',
        },
    };

    return mount(
        await Shopware.Component.build('swag-paypal-cross-border'),
        Shopware.Utils.object.mergeWith(options, customOptions),
    );
}

describe('swag-paypal-webhook', () => {
    it('should be a Vue.js component', async () => {
        const wrapper = await createWrapper();

        await wrapper.vm.$nextTick();

        expect(wrapper.vm).toBeTruthy();
    });

    it('should render the correct country override options', async () => {
        const wrapper = await createWrapper();

        await wrapper.vm.$nextTick();

        expect(wrapper.vm.countryOverrideOptions.length).toBe(8);

        // Auto determination is always first
        expect(wrapper.vm.countryOverrideOptions[0].value).toBe(null);
        expect(wrapper.vm.countryOverrideOptions[0].label).toBe('swag-paypal.cross-border.crossBorderBuyerCountryAuto');
    });
});
