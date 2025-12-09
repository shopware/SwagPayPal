import { mount } from '@vue/test-utils';
import SwagPayPalSettingsAdvanced from '.';
import type SwagPayPalSetting from 'SwagPayPal/app/component/swag-paypal-setting';

Shopware.Component.register('swag-paypal-settings-advanced', Promise.resolve(SwagPayPalSettingsAdvanced));
Shopware.Component.register('swag-paypal-setting', () => import('SwagPayPal/app/component/swag-paypal-setting'));

async function createWrapper() {
    return mount(
        await Shopware.Component.build('swag-paypal-settings-advanced') as typeof SwagPayPalSettingsAdvanced,
        {
            global: {
                stubs: {
                    'sw-card': await wrapTestComponent('sw-card', { sync: true }),
                    'swag-paypal-setting': {
                        name: 'swag-paypal-setting',
                        props: ['path'],
                        template: '<div class="swag-paypal-setting"></div>',
                    },
                    'swag-paypal-settings-webhook': true,
                },
            },
        },
    );
}

describe('swag-paypal-settings-advanced', () => {
    it('should be a Vue.js component', async () => {
        const wrapper = await createWrapper();

        expect(wrapper.vm).toBeTruthy();
    });

    it('should have settings cards', async () => {
        const wrapper = await createWrapper();

        const cardClasses = wrapper
            .findAll('.mt-card')
            .map((el) => el.classes())
            .flat()
            .filter((cl) => cl.startsWith('swag-paypal'));

        expect(cardClasses).toEqual([
            'swag-paypal-settings-environment',
            'swag-paypal-settings-cross-border',
        ]);
    });

    it('should have settings', async () => {
        const wrapper = await createWrapper();

        const components = wrapper.findAllComponents<typeof SwagPayPalSetting>({ name: 'swag-paypal-setting' });
        const settings = Object.fromEntries(components.map((el) => [el.props().path, el]));

        expect(Object.keys(settings)).toEqual([
            'SwagPayPal.settings.isLocalEnvironment',
            'SwagPayPal.settings.crossBorderMessagingEnabled',
            'SwagPayPal.settings.crossBorderBuyerCountry',
        ]);

        expect(settings['SwagPayPal.settings.crossBorderBuyerCountry'].vm.$attrs.options)
            .toBe(wrapper.vm.countryOverrideOptions);
    });

    it('should have cross-border information', async () => {
        const wrapper = await createWrapper();

        const alert = wrapper.find('.swag-paypal-settings-cross-border .mt-banner');

        expect(alert.exists()).toBe(true);
        expect(alert.classes()).toContain('swag-paypal-settings-cross-border__warning-text');

        const info = wrapper.find('.swag-paypal-settings-cross-border__info-text');

        expect(info.exists()).toBe(true);
        expect(info.text()).toBe('swag-paypal-settings.crossBorder.info');
    });

    it('should have paypal-callback information', async () => {
        const wrapper = await createWrapper();

        const info = wrapper.find('.swag-paypal-settings-environment__info-text');

        expect(info.exists()).toBe(true);
        expect(info.text()).toBe('swag-paypal-settings.localEnvironment.info');
    });
});
