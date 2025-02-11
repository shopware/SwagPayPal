import { mount } from '@vue/test-utils';
import SwagPayPalMethodDomainAssociation from '.';

Shopware.Component.register('swag-paypal-method-domain-association', Promise.resolve(SwagPayPalMethodDomainAssociation));

async function createWrapper(active: boolean = true) {
    return mount(
        await Shopware.Component.build('swag-paypal-method-domain-association') as typeof SwagPayPalMethodDomainAssociation,
        {
            global: {
                stubs: {
                    'sw-alert': await wrapTestComponent('sw-alert', { sync: true }),
                    'sw-alert-deprecated': await wrapTestComponent('sw-alert-deprecated', { sync: true }),
                    'sw-external-link': await wrapTestComponent('sw-external-link', { sync: true }),
                    'sw-external-link-deprecated': await wrapTestComponent('sw-external-link-deprecated', { sync: true }),
                },
            },
            props: {
                paymentMethod: { active } as TEntity<'payment_method'>,
            },
        },
    );
}

describe('swag-paypal-method-domain-association', () => {
    it('should be a Vue.js component', async () => {
        const wrapper = await createWrapper();

        expect(wrapper.vm).toBeTruthy();
    });

    it('should show', async () => {
        const wrapper = await createWrapper();

        expect(wrapper.vm.show).toBe(true);

        const alert = wrapper.findComponent<VueComponent>('.sw-alert');
        expect(alert.exists()).toBe(true);
        expect(alert.isVisible()).toBe(true);
    });

    it('should hide', async () => {
        const wrapper = await createWrapper(false);

        const alert = wrapper.findComponent<VueComponent>('.sw-alert');
        expect(alert.exists()).toBe(true);

        expect(wrapper.vm.show).toBe(false);
        expect(alert.isVisible()).toBe(false);
    });

    it('should hide on close', async () => {
        const wrapper = await createWrapper();

        const alert = wrapper.findComponent<VueComponent>('.sw-alert');
        expect(alert.exists()).toBe(true);

        expect(wrapper.vm.show).toBe(true);
        expect(alert.isVisible()).toBe(true);

        alert.get('.sw-alert__close').trigger('click');
        await flushPromises();

        expect(wrapper.vm.show).toBe(false);
        expect(alert.isVisible()).toBe(false);
        expect(localStorage.getItem('domain-association-hidden')).toBe('true');
    });

    it('should link dependend on sandbox', async () => {
        const wrapper = await createWrapper();

        wrapper.vm.settingsStore.setConfig(null, { 'SwagPayPal.settings.sandbox': true });
        expect(wrapper.vm.domainAssociationLink).toBe('https://www.sandbox.paypal.com/uccservicing/apm/applepay');

        wrapper.vm.settingsStore.setConfig(null, { 'SwagPayPal.settings.sandbox': false });
        expect(wrapper.vm.domainAssociationLink).toBe('https://www.paypal.com/uccservicing/apm/applepay');
    });

    it('should have external link', async () => {
        const wrapper = await createWrapper();

        const link = wrapper.findComponent<VueComponent>('.sw-external-link');
        expect(link.exists()).toBe(true);
        expect(link.attributes('href')).toBe(wrapper.vm.domainAssociationLink);
    });
});
