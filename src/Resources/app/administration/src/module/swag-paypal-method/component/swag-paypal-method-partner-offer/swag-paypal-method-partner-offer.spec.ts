import { mount } from '@vue/test-utils';
import SwagPayPalMethodPartnerOffer from '.';

Shopware.Component.register('swag-paypal-method-partner-offer', Promise.resolve(SwagPayPalMethodPartnerOffer));

async function createWrapper() {
    return mount(
        await Shopware.Component.build('swag-paypal-method-partner-offer') as typeof SwagPayPalMethodPartnerOffer,
        {
            global: {
                stubs: {
                    'mt-link': { template: '<a :href="$attrs.href"><slot></slot></a>' },
                },
            },
        },
    );
}

describe('swag-paypal-method-partner-offer', () => {
    beforeEach(() => {
        localStorage.clear();
        Shopware.Store.get('session').currentLocale = 'de-DE';
        jest.useFakeTimers();
        jest.setSystemTime(new Date(2026, 8, 3));
    });

    afterEach(() => {
        jest.useRealTimers();
    });

    it('should be a Vue.js component', async () => {
        const wrapper = await createWrapper();

        expect(wrapper.vm).toBeTruthy();
    });

    it('should show while the offer is running', async () => {
        const wrapper = await createWrapper();

        expect(wrapper.vm.show).toBe(true);
        expect(wrapper.find('.swag-paypal-method-partner-offer').exists()).toBe(true);
    });

    it('should show on the last day of the offer', async () => {
        jest.setSystemTime(new Date(2026, 11, 31, 23, 59, 59));

        const wrapper = await createWrapper();

        expect(wrapper.vm.show).toBe(true);
    });

    it('should not show after the offer ended', async () => {
        jest.setSystemTime(new Date(2027, 0, 1));

        const wrapper = await createWrapper();

        expect(wrapper.vm.show).toBe(false);
        expect(wrapper.find('.swag-paypal-method-partner-offer').exists()).toBe(false);
    });

    it.each([
        'en-GB',
        'nl-NL',
        null,
    ])('should not show for the locale %s', async (currentLocale) => {
        Shopware.Store.get('session').currentLocale = currentLocale;

        const wrapper = await createWrapper();

        expect(wrapper.vm.isGermanMerchant).toBe(false);
        expect(wrapper.find('.swag-paypal-method-partner-offer').exists()).toBe(false);
    });

    it('should stay hidden once closed', async () => {
        const wrapper = await createWrapper();

        wrapper.vm.onCloseBanner();
        await flushPromises();

        expect(wrapper.vm.show).toBe(false);
        expect(wrapper.find('.swag-paypal-method-partner-offer').exists()).toBe(false);
        expect(localStorage.getItem('swag-paypal-businesskredit-offer-hidden')).toBe('true');
    });

    it('should not show when it was closed before', async () => {
        localStorage.setItem('swag-paypal-businesskredit-offer-hidden', 'true');

        const wrapper = await createWrapper();

        expect(wrapper.vm.show).toBe(false);
    });

    it('should link to the landing page', async () => {
        const wrapper = await createWrapper();

        const link = wrapper.find('a');
        expect(link.exists()).toBe(true);
        expect(link.attributes('href')).toBe('https://www.shopware.com/de/paypal-businesskredit');
    });
});
