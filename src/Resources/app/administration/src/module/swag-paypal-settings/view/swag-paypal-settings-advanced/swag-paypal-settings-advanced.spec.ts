import { config, mount } from '@vue/test-utils';
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
    afterEach(() => {
        Shopware.Store.get('swagPayPalSettings').$reset();
    });

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
            'swag-paypal-settings-logs',
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
            'SwagPayPal.settings.logLevel',
        ]);

        expect(settings['SwagPayPal.settings.crossBorderBuyerCountry'].vm.$attrs.options)
            .toBe(wrapper.vm.countryOverrideOptions);
        expect(settings['SwagPayPal.settings.logLevel'].vm.$attrs.options)
            .toBe(wrapper.vm.logLevelOptions);
    });

    it('should offer every log level and turning the logs off', async () => {
        const wrapper = await createWrapper();

        expect(wrapper.vm.logLevelOptions).toEqual([
            { value: 0, label: 'swag-paypal-settings.logs.level.off' },
            { value: 100, label: 'swag-paypal-settings.logs.level.debug' },
            { value: 200, label: 'swag-paypal-settings.logs.level.info' },
            { value: 250, label: 'swag-paypal-settings.logs.level.notice' },
            { value: 300, label: 'swag-paypal-settings.logs.level.warning' },
            { value: 400, label: 'swag-paypal-settings.logs.level.error' },
        ]);
    });

    it('should change the log level only for all Sales Channels', async () => {
        const wrapper = await createWrapper();
        const logLevel = () => wrapper
            .findAllComponents<typeof SwagPayPalSetting>({ name: 'swag-paypal-setting' })
            .find((el) => el.props().path === 'SwagPayPal.settings.logLevel');

        expect(logLevel()?.vm.$attrs.disabled).toBe(false);

        Shopware.Store.get('swagPayPalSettings').salesChannel = 'a1b2c3d4e5f60718293a4b5c6d7e8f90';
        await flushPromises();

        expect(logLevel()?.vm.$attrs.disabled).toBe(true);
    });

    it('should link the logs to the Diagnostics tab', async () => {
        // i18n-t renders the link only for a message with its placeholder
        const i18n = config.global.plugins.find((plugin) => 'global' in plugin) as unknown as {
            global: { mergeLocaleMessage: (locale: string, messages: Record<string, string>) => void };
        };
        i18n.global.mergeLocaleMessage('en', { 'swag-paypal-settings.logs.info': 'View them in the {diagnostics} tab.' });

        const wrapper = await createWrapper();

        const link = wrapper.findComponent<{ props: () => { to: unknown } }>('.swag-paypal-settings-logs__info-text .mt-link');

        expect(link.exists()).toBe(true);
        expect(link.props('to')).toEqual({ name: 'swag.paypal.settings.index.diagnostics' });
        expect(link.text()).toBe('swag-paypal-settings.tabs.diagnostics');
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
        expect(info.text()).toBe('swag-paypal-settings.environment.info');
    });
});
