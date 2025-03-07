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
                provide: {
                    SwagPayPalSettingsService: {},
                },
                mocks: { $t: (key: string) => key },
                stubs: {
                    'swag-paypal-onboarding-button': true,
                    'sw-container': await wrapTestComponent('sw-container', { sync: true }),

                    // swag-paypal-setting deps
                    'swag-paypal-setting': await Shopware.Component.build('swag-paypal-setting'),
                    'sw-inherit-wrapper': await wrapTestComponent('sw-inherit-wrapper', { sync: true }),
                    'sw-help-text': true,
                    'sw-inheritance-switch': true,
                    'sw-single-select': true,
                    'sw-button-process': true,
                    'sw-product-variant-info': true,
                    'sw-entity-multi-id-select': true,
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
            .findAll('.mt-card')
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
        global.activeAclRoles = ['swag_paypal.editor'];
        const wrapper = await createWrapper();

        const liveSwitch = wrapper.find('.swag-paypal-settings-live-credentials input');
        expect(liveSwitch.exists()).toBe(true);

        const sandboxSwitch = wrapper.find('.swag-paypal-settings-sandbox-credentials input');
        expect(sandboxSwitch.exists()).toBe(true);

        expect(store.isSandbox).toBe(false);
        expect(liveSwitch.attributes().checked).toBe('');
        expect(sandboxSwitch.attributes().checked).toBeUndefined();

        // Switch trough store
        store.set('SwagPayPal.settings.sandbox', true);

        await wrapper.vm.$nextTick();

        expect(liveSwitch.attributes().checked).toBeUndefined();
        expect(sandboxSwitch.attributes().checked).toBe('');

        // Switch trough UI
        await sandboxSwitch.setValue(false);
        expect(liveSwitch.attributes().checked).toBe('');
        expect(sandboxSwitch.attributes().checked).toBeUndefined();

        await liveSwitch.setValue(false);
        expect(liveSwitch.attributes().checked).toBeUndefined();
        expect(sandboxSwitch.attributes().checked).toBe('');
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

        expect(live.map((setting) => settings[setting]?.vm.formAttrs.disabled)).toStrictEqual(Array(3).fill(true));
        expect(sandbox.map((setting) => settings[setting]?.vm.formAttrs.disabled)).toStrictEqual(Array(3).fill(false));

        store.set('SwagPayPal.settings.sandbox', false);
        await wrapper.vm.$nextTick();

        expect(live.map((setting) => settings[setting]?.vm.formAttrs.disabled)).toStrictEqual(Array(3).fill(false));
        expect(sandbox.map((setting) => settings[setting]?.vm.formAttrs.disabled)).toStrictEqual(Array(3).fill(true));
    });
});
