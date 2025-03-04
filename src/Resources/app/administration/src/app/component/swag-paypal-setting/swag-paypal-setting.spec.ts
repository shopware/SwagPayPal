import { mount } from '@vue/test-utils';
import SettingsFixture from '../../store/settings.fixture';
import { INTENTS } from '../../../constant/swag-paypal-settings.constant';
import SwagPayPalSetting from '.';

Shopware.Component.register('swag-paypal-setting', Promise.resolve(SwagPayPalSetting));

async function createWrapper(props: $TSFixMe = { path: 'SwagPayPal.settings.clientId' }, translations: Record<string, string> = {}) {
    return mount(
        await Shopware.Component.build('swag-paypal-setting') as typeof SwagPayPalSetting,
        {

            props,
            global: {
                mocks: { $t: (key: string) => translations[key] ?? key },
                stubs: {
                    'sw-inherit-wrapper': await wrapTestComponent('sw-inherit-wrapper', { sync: true }),
                    'sw-inheritance-switch': await wrapTestComponent('sw-inheritance-switch', { sync: true }),
                    'sw-help-text': await wrapTestComponent('sw-help-text', { sync: true }),
                    'sw-icon': await wrapTestComponent('sw-icon', { sync: true }),
                    // type === string + options
                    'sw-single-select': await wrapTestComponent('sw-single-select', { sync: true }),
                    'sw-select-base': await wrapTestComponent('sw-select-base', { sync: true }),
                    'sw-highlight-text': await wrapTestComponent('sw-highlight-text', { sync: true }),
                    'sw-select-result': await wrapTestComponent('sw-select-result', { sync: true }),
                    'sw-select-result-list': await wrapTestComponent('sw-select-result-list', { sync: true }),
                    // field bases
                    'sw-contextual-field': await wrapTestComponent('sw-contextual-field', { sync: true }),
                    'sw-block-field': await wrapTestComponent('sw-block-field', { sync: true }),
                    'sw-base-field': await wrapTestComponent('sw-base-field', { sync: true }),
                    'sw-loader': await wrapTestComponent('sw-loader', { sync: true }),
                    'sw-ai-copilot-badge': await wrapTestComponent('sw-ai-copilot-badge', { sync: true }),
                    'sw-field-error': await wrapTestComponent('sw-field-error', { sync: true }),
                },
            },
        },
    );
}

describe('swag-paypal-setting', () => {
    const store = Shopware.Store.get('swagPayPalSettings');

    beforeEach(() => {
        global.activeAclRoles = ['swag_paypal.editor'];
    });

    it('should be a Vue.js component', async () => {
        const wrapper = await createWrapper();

        expect(wrapper.vm).toBeTruthy();
    });

    it('should have computed label, helpText and hintText', async () => {
        const wrapper = await createWrapper(
            { path: 'SwagPayPal.settings.clientId' },
            {
                'swag-paypal-setting.label.clientId': 'Label text',
                'swag-paypal-setting.helpText.clientId': 'Help text',
                'swag-paypal-setting.hintText.clientId': 'Hint text',
            },
        );

        expect(wrapper.vm.label).toBe('Label text');
        expect(wrapper.vm.helpText).toBe('Help text');
        expect(wrapper.vm.hintText).toBe('Hint text');
        expect(wrapper.vm.attrs.label).toBe(wrapper.vm.label);
        expect(wrapper.vm.attrs.helpText).toBe(wrapper.vm.helpText);
        expect(wrapper.vm.attrs.hintText).toBe(wrapper.vm.hintText);
    });

    it('should have not overridden label, helpText and hintText', async () => {
        const wrapper = await createWrapper(
            {
                path: 'SwagPayPal.settings.clientId',
                label: 'Overridden label',
                helpText: undefined,
                hintText: 'Overridden hint text',
            },
            {
                'swag-paypal-setting.label.clientId': 'Label text',
                'swag-paypal-setting.helpText.clientId': 'Help text',
                'swag-paypal-setting.hintText.clientId': 'Hint text',
            },
        );

        expect(wrapper.vm.label).toBe('Label text');
        expect(wrapper.vm.helpText).toBe('Help text');
        expect(wrapper.vm.hintText).toBe('Hint text');
        expect(wrapper.vm.attrs.label).toBe('Overridden label');
        expect(wrapper.vm.attrs.helpText).toBeUndefined();
        expect(wrapper.vm.attrs.hintText).toBe('Overridden hint text');
    });

    it('should have normalized attrs', async () => {
        const wrapper = await createWrapper({
            path: 'SwagPayPal.settings.clientId',
            'not-camel-case': 'value',
            'help-text': 'Help text',
        });

        expect(wrapper.vm.attrs).toStrictEqual({
            notCamelCase: 'value',
            helpText: 'Help text',
        });
    });

    it('should have attrs', async () => {
        const attrs = {
            bordered: true,
            disabled: true,
            error: { code: 'TEST', detail: 'Test error' },
            helpText: 'Help text',
            label: 'Label text',
            labelProperty: 'label',
            options: [],
            required: true,
            valueProperty: 'value',
        };

        const wrapper = await createWrapper({ path: 'SwagPayPal.settings.clientId', ...attrs });

        expect(wrapper.vm.type).toBe('string');
        expect(wrapper.vm.attrs).toStrictEqual(attrs);
        expect(wrapper.vm.wrapperAttrs).toStrictEqual({
            disabled: true,
            helpText: 'Help text',
            label: 'Label text',
            required: true,
        });
        expect(wrapper.vm.formAttrs).toStrictEqual({
            disabled: true,
            error: { code: 'TEST', detail: 'Test error' },
            labelProperty: 'label',
            options: [],
            valueProperty: 'value',
        });
    });

    it('should find translations', async () => {
        const wrapper = await createWrapper(
            { path: 'SwagPayPal.settings.clientId' },
            { transOne: 'Label text', transTwo: 'Help text' },
        );

        expect(wrapper.vm.tif('non-existing')).toBeNull();
        expect(wrapper.vm.tif('transOne')).toBe('Label text');
        expect(wrapper.vm.tif('transOne', 'transTwo')).toBe('Label text');
        expect(wrapper.vm.tif('non-existing', 'transTwo')).toBe('Help text');
    });

    it('should be a text field without inheritance', async () => {
        store.setConfig(null, SettingsFixture.WithCredentials);

        const wrapper = await createWrapper(
            { path: 'SwagPayPal.settings.clientId' },
            { 'swag-paypal-setting.label.clientId': 'Client ID' },
        );

        // computed properties
        expect(wrapper.vm.value).toBe('some-client-id');
        expect(wrapper.vm.inheritedValue).toBeUndefined();
        expect(wrapper.vm.hasParent).toBe(false);
        expect(wrapper.vm.pathDomainless).toBe('clientId');
        expect(wrapper.vm.disabled).toBe(false);
        expect(wrapper.vm.type).toBe('string');
        expect(wrapper.vm.label).toBe('Client ID');
        expect(wrapper.vm.helpText).toBeNull();
        expect(wrapper.vm.hintText).toBeNull();

        const input = wrapper.get<HTMLInputElement>('input[type="text"]');
        expect(input.attributes().value).toBe('some-client-id');
        expect(input.attributes().name).toBe('SwagPayPal.settings.clientId');
        expect(input.attributes().disabled).toBeUndefined();
    });

    it('should be a boolean field without inheritance', async () => {
        store.setConfig(null, SettingsFixture.WithCredentials);

        const wrapper = await createWrapper(
            { path: 'SwagPayPal.settings.sandbox' },
            { 'swag-paypal-setting.label.sandbox': 'Sandbox' },
        );

        // computed properties
        expect(wrapper.vm.value).toBe(false);
        expect(wrapper.vm.inheritedValue).toBeUndefined();
        expect(wrapper.vm.hasParent).toBe(false);
        expect(wrapper.vm.pathDomainless).toBe('sandbox');
        expect(wrapper.vm.disabled).toBe(false);
        expect(wrapper.vm.type).toBe('boolean');
        expect(wrapper.vm.label).toBe('Sandbox');
        expect(wrapper.vm.helpText).toBeNull();
        expect(wrapper.vm.hintText).toBeNull();

        const input = wrapper.get<HTMLInputElement>('input[type="checkbox"]');
        expect(input.attributes().checked).toBeUndefined();
        expect(input.attributes().name).toBe('SwagPayPal.settings.sandbox');
        expect(input.attributes().disabled).toBeUndefined();
    });

    it('should be a select field without inheritance', async () => {
        store.setConfig(null, SettingsFixture.WithCredentials);
        const options = INTENTS.map((intent) => ({ value: intent, label: intent }));

        const wrapper = await createWrapper(
            { path: 'SwagPayPal.settings.intent', options },
            { 'swag-paypal-setting.label.intent': 'Intent' },
        );

        // computed properties
        expect(wrapper.vm.value).toBe('CAPTURE');
        expect(wrapper.vm.inheritedValue).toBeUndefined();
        expect(wrapper.vm.hasParent).toBe(false);
        expect(wrapper.vm.pathDomainless).toBe('intent');
        expect(wrapper.vm.disabled).toBe(false);
        expect(wrapper.vm.type).toBe('string');
        expect(wrapper.vm.label).toBe('Intent');
        expect(wrapper.vm.helpText).toBeNull();
        expect(wrapper.vm.hintText).toBeNull();

        const field = wrapper.findComponent<VueComponent>('.sw-single-select');

        expect(field.exists()).toBe(true);
        expect(field.vm.value).toBe('CAPTURE');
        expect(field.vm.$attrs.name).toBe('SwagPayPal.settings.intent');
        expect(field.vm.$attrs.disabled).toBe(false);

        Object.keys(wrapper.vm.formAttrs).forEach((key: string) => {
            if (key === 'options') {
                expect(field.vm).toHaveProperty(key);
                expect(field.vm[key]).toStrictEqual(wrapper.vm.formAttrs[key]);
            } else {
                expect(field.vm.$attrs).toHaveProperty(key);
                expect(field.vm.$attrs[key]).toBe(wrapper.vm.formAttrs[key]);
            }
        });
    });

    it('should be a text field with inheritance', async () => {
        store.setConfig(null, SettingsFixture.WithCredentials);
        store.setConfig('some-sales-channel', SettingsFixture.All);
        store.salesChannel = 'some-sales-channel';

        const wrapper = await createWrapper(
            { path: 'SwagPayPal.settings.clientId' },
            { 'swag-paypal-setting.label.clientId': 'Client ID' },
        );

        // computed properties
        expect(wrapper.vm.value).toBe('');
        expect(wrapper.vm.inheritedValue).toBe('some-client-id');
        expect(wrapper.vm.hasParent).toBe(true);
        expect(wrapper.vm.pathDomainless).toBe('clientId');
        expect(wrapper.vm.disabled).toBe(false);
        expect(wrapper.vm.type).toBe('string');
        expect(wrapper.vm.wrapperAttrs.label).toBe('Client ID');

        // field shows actual value
        const input = wrapper.get<HTMLInputElement>('input[type="text"]');
        expect(input.attributes().value).toBe('');
        expect(input.attributes().name).toBe('SwagPayPal.settings.clientId');
        expect(input.attributes().disabled).toBeUndefined();

        // inheritance switch exists and is not inherited
        const inheritSwitch = wrapper.findComponent<VueComponent>('.sw-inheritance-switch');
        expect(inheritSwitch.exists()).toBe(true);
        expect(inheritSwitch.vm.isInherited).toBe(false);

        // Switch inheritance - value should be restored
        const icon = inheritSwitch.getComponent({ name: 'mt-icon' });
        await icon.trigger('click');

        expect(inheritSwitch.vm.isInherited).toBe(true);
        expect(wrapper.vm.value).toBeUndefined();
        expect(wrapper.vm.inheritedValue).toBe('some-client-id');
        expect(input.attributes().disabled).toBe('');
        expect(input.attributes().value).toBe('some-client-id');

        // Switch to "All Sales Channels" - inheritance should be disabled
        store.salesChannel = null;

        await wrapper.vm.$nextTick();

        expect(inheritSwitch.exists()).toBe(false);
    });

    it('should be a select field with inheritance', async () => {
        store.setConfig(null, SettingsFixture.Default);
        store.setConfig('some-sales-channel', {
            'SwagPayPal.settings.intent': 'AUTHORIZE',
        });
        store.salesChannel = 'some-sales-channel';

        const wrapper = await createWrapper(
            {
                path: 'SwagPayPal.settings.intent',
                options: INTENTS.map((intent) => ({ value: intent, label: intent })),
            },
            { 'swag-paypal-setting.label.intent': 'Intent' },
        );

        // computed properties
        expect(wrapper.vm.value).toBe('AUTHORIZE');
        expect(wrapper.vm.inheritedValue).toBe('CAPTURE');
        expect(wrapper.vm.hasParent).toBe(true);
        expect(wrapper.vm.pathDomainless).toBe('intent');
        expect(wrapper.vm.disabled).toBe(false);
        expect(wrapper.vm.type).toBe('string');
        expect(wrapper.vm.wrapperAttrs.label).toBe('Intent');

        // field shows actual value
        const field = wrapper.findComponent<VueComponent>('.sw-single-select');
        expect(field.exists()).toBe(true);
        expect(field.vm.value).toBe('AUTHORIZE');
        expect(field.vm.$attrs.disabled).toBe(false);

        // inheritance switch exists and is not inherited
        const inheritSwitch = wrapper.findComponent<VueComponent>('.sw-inheritance-switch');
        expect(inheritSwitch.exists()).toBe(true);
        expect(inheritSwitch.vm.isInherited).toBe(false);

        // Switch inheritance - value should be restored
        const icon = inheritSwitch.findComponent<VueComponent>({ name: 'mt-icon' });
        expect(icon.exists()).toBe(true);
        await icon.trigger('click');

        expect(inheritSwitch.vm.isInherited).toBe(true);
        expect(wrapper.vm.value).toBeUndefined();
        expect(wrapper.vm.inheritedValue).toBe('CAPTURE');
        expect(field.vm.$attrs.disabled).toBe(true);
        expect(field.vm.value).toBe('CAPTURE');

        // Switch to "All Sales Channels" - inheritance should be disabled
        store.salesChannel = null;

        await wrapper.vm.$nextTick();

        expect(inheritSwitch.exists()).toBe(false);
    });

    it('should be a boolean field with inheritance', async () => {
        store.setConfig(null, SettingsFixture.Default);
        store.setConfig('some-sales-channel', { 'SwagPayPal.settings.sandbox': true });
        store.salesChannel = 'some-sales-channel';

        const wrapper = await createWrapper(
            { path: 'SwagPayPal.settings.sandbox' },
            { 'swag-paypal-setting.label.sandbox': 'Sandbox' },
        );

        // computed properties
        expect(wrapper.vm.value).toBe(true);
        expect(wrapper.vm.inheritedValue).toBe(false);
        expect(wrapper.vm.hasParent).toBe(true);
        expect(wrapper.vm.pathDomainless).toBe('sandbox');
        expect(wrapper.vm.disabled).toBe(false);
        expect(wrapper.vm.type).toBe('boolean');

        // field shows actual value
        const input = wrapper.get<HTMLInputElement>('input[type="checkbox"]');
        expect(input.attributes().checked).toBe('');
        expect(input.attributes().disabled).toBeUndefined();

        // inheritance switch exists and is not inherited
        const inheritSwitch = wrapper.findComponent<VueComponent>('.sw-inheritance-switch');
        expect(inheritSwitch.exists()).toBe(true);
        expect(inheritSwitch.vm.isInherited).toBe(false);

        // Switch inheritance - value should be restored
        const icon = inheritSwitch.findComponent('.mt-icon');
        expect(icon.exists()).toBe(true);
        await icon.trigger('click');

        expect(inheritSwitch.vm.isInherited).toBe(true);
        expect(wrapper.vm.value).toBeUndefined();
        expect(wrapper.vm.inheritedValue).toBe(false);
        expect(input.attributes().disabled).toBe('');
        expect(input.attributes().checked).toBeUndefined();

        // Switch to "All Sales Channels" - inheritance should be disabled
        store.salesChannel = null;

        await wrapper.vm.$nextTick();

        expect(inheritSwitch.exists()).toBe(false);
    });
});
