import { mount } from '@vue/test-utils';
import SwagPayPalSettingsGeneral from '.';
import SettingsFixture from '../../../../app/store/settings.fixture';
import type SwagPayPalSetting from 'SwagPayPal/app/component/swag-paypal-setting';

Shopware.Component.register('swag-paypal-settings-general', Promise.resolve(SwagPayPalSettingsGeneral));
Shopware.Component.register('swag-paypal-setting', () => import('SwagPayPal/app/component/swag-paypal-setting'));

async function createWrapper() {
    return mount(
        await Shopware.Component.build('swag-paypal-settings-general') as typeof SwagPayPalSettingsGeneral,
        {
            global: {
                provide: { acl: { can: () => true } },
                mocks: { $t: (key: string) => key },
                stubs: {
                    'swag-paypal-setting': await Shopware.Component.build('swag-paypal-setting'),
                    'sw-inherit-wrapper': await wrapTestComponent('sw-inherit-wrapper', { sync: true }),
                    'sw-switch-field': await wrapTestComponent('sw-switch-field', { sync: true }),
                    'sw-switch-field-deprecated': await wrapTestComponent('sw-switch-field-deprecated', { sync: true }),
                    'sw-checkbox-field': await wrapTestComponent('sw-checkbox-field', { sync: true }),
                    'sw-checkbox-field-deprecated': await wrapTestComponent('sw-checkbox-field-deprecated', { sync: true }),

                    'sw-card': await wrapTestComponent('sw-card', { sync: true }),
                    'sw-card-deprecated': await wrapTestComponent('sw-card-deprecated', { sync: true }),
                    'sw-base-field': await wrapTestComponent('sw-base-field', { sync: true }),
                },
            },
        },
    );
}

describe('swag-paypal-settings-general', () => {
    const store = Shopware.Store.get('swagPayPalSettings');

    beforeEach(() => {
        store.setConfig(null, SettingsFixture.Default);
    });

    it('should be a Vue.js component', async () => {
        const wrapper = await createWrapper();

        expect(wrapper.vm).toBeTruthy();
    });

    it('should have settings cards', async () => {
        const wrapper = await createWrapper();

        const cardClasses = wrapper
            .findAll('.sw-card')
            .map((el) => el.classes())
            .flat()
            .filter((cl) => cl.startsWith('swag-paypal'));

        expect(cardClasses).toEqual([
            'swag-paypal-settings-live-credentials',
            'swag-paypal-settings-sandbox-credentials',
            'swag-paypal-settings-behavior',
            'swag-paypal-settings-vaulting',
            'swag-paypal-settings-acdc',
            'swag-paypal-settings-pui',
        ]);
    });

    it('should have settings', async () => {
        const wrapper = await createWrapper();

        const components = wrapper.findAllComponents<typeof SwagPayPalSetting>({ name: 'swag-paypal-setting' });
        const settings = Object.fromEntries(components.map((el) => [el.props().path, el]));

        expect(Object.keys(settings)).toEqual([
            'SwagPayPal.settings.sandbox',
            'SwagPayPal.settings.clientId',
            'SwagPayPal.settings.clientSecret',
            'SwagPayPal.settings.merchantPayerId',
            'SwagPayPal.settings.clientIdSandbox',
            'SwagPayPal.settings.clientSecretSandbox',
            'SwagPayPal.settings.merchantPayerIdSandbox',
            'SwagPayPal.settings.intent',
            'SwagPayPal.settings.submitCart',
            'SwagPayPal.settings.brandName',
            'SwagPayPal.settings.landingPage',
            'SwagPayPal.settings.sendOrderNumber',
            'SwagPayPal.settings.orderNumberPrefix',
            'SwagPayPal.settings.orderNumberSuffix',
            'SwagPayPal.settings.excludedProductIds',
            'SwagPayPal.settings.excludedProductStreamIds',
            'SwagPayPal.settings.acdcForce3DS',
            'SwagPayPal.settings.puiCustomerServiceInstructions',
        ]);
    });

    it('should invert sandbox toggle for live and sandbox', async () => {
        const wrapper = await createWrapper();

        const liveSwitch = wrapper.findComponent<VueComponent>('.swag-paypal-settings-live-credentials .sw-field--switch');
        expect(liveSwitch.exists()).toBe(true);

        const sandboxSwitch = wrapper.findComponent<VueComponent>('.swag-paypal-settings-sandbox-credentials .sw-field--switch');
        expect(sandboxSwitch.exists()).toBe(true);

        expect(store.isSandbox).toBe(false);
        expect(liveSwitch.vm.value).toBe(true);
        expect(sandboxSwitch.vm.value).toBe(false);

        // Switch trough store
        store.set('SwagPayPal.settings.sandbox', true);

        await wrapper.vm.$nextTick();

        expect(liveSwitch.vm.value).toBe(false);
        expect(sandboxSwitch.vm.value).toBe(true);

        // Switch trough UI
        await sandboxSwitch.find('input').setValue(false);
        expect(liveSwitch.vm.value).toBe(true);
        expect(sandboxSwitch.vm.value).toBe(false);

        await liveSwitch.find('input').setValue(false);
        expect(liveSwitch.vm.value).toBe(false);
        expect(sandboxSwitch.vm.value).toBe(true);
    });

    it('should disable credentials fields on sandbox toggle', async () => {
        const wrapper = await createWrapper();

        const components = wrapper.findAllComponents<typeof SwagPayPalSetting>({ name: 'swag-paypal-setting' });
        const settings = Object.fromEntries(components.map((el) => [el.props().path, el]));

        const live = [
            'SwagPayPal.settings.clientId',
            'SwagPayPal.settings.clientSecret',
            'SwagPayPal.settings.merchantPayerId',
        ];

        const sandbox = [
            'SwagPayPal.settings.clientIdSandbox',
            'SwagPayPal.settings.clientSecretSandbox',
            'SwagPayPal.settings.merchantPayerIdSandbox',
        ];

        store.set('SwagPayPal.settings.sandbox', true);
        await wrapper.vm.$nextTick();

        expect(live.map((setting) => settings[setting]?.vm.formAttrs.disabled)).toContain(true);
        expect(sandbox.map((setting) => settings[setting]?.vm.formAttrs.disabled)).toContain(false);

        store.set('SwagPayPal.settings.sandbox', false);
        await wrapper.vm.$nextTick();

        expect(live.map((setting) => settings[setting]?.vm.formAttrs.disabled)).toContain(false);
        expect(sandbox.map((setting) => settings[setting]?.vm.formAttrs.disabled)).toContain(true);
    });
});
