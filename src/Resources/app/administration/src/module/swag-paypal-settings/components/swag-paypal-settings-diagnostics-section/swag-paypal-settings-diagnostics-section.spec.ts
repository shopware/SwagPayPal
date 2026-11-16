import { mount } from '@vue/test-utils';
import SwagPayPalSettingsDiagnosticsSection from '.';

Shopware.Component.register('swag-paypal-settings-diagnostics-section', Promise.resolve(SwagPayPalSettingsDiagnosticsSection));

const BLOCK = '.swag-paypal-settings-diagnostics-section';

async function createWrapper(props: Record<string, unknown> = {}) {
    return mount(
        await Shopware.Component.build('swag-paypal-settings-diagnostics-section') as typeof SwagPayPalSettingsDiagnosticsSection,
        {
            props: { title: 'Reachability', ...props },
            slots: { default: '<p class="row">Row</p>' },
        },
    );
}

describe('swag-paypal-settings-diagnostics-section', () => {
    it('should show the title above its rows', async () => {
        const wrapper = await createWrapper();

        expect(wrapper.find(`${BLOCK}__title`).text()).toBe('Reachability');
        expect(wrapper.find('.row').exists()).toBe(true);
        expect(wrapper.find(`${BLOCK}__description`).exists()).toBe(false);
    });

    it('should show the description when given', async () => {
        const wrapper = await createWrapper({ description: 'Runs from this server.' });

        expect(wrapper.find(`${BLOCK}__description`).text()).toBe('Runs from this server.');
    });
});
