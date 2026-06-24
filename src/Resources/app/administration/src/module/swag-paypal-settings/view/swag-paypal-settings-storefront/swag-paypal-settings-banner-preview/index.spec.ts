import { mount } from '@vue/test-utils';
import SwagPayPalSettingsBannerPreview from './index';

Shopware.Component.register('swag-paypal-settings-banner-preview', Promise.resolve(SwagPayPalSettingsBannerPreview));

async function createWrapper() {
    return mount(
        await Shopware.Component.build('swag-paypal-settings-banner-preview') as typeof SwagPayPalSettingsBannerPreview,
        {
            global: {
                stubs: {
                    'sw-icon': true,
                    'mt-skeleton-bar': true,
                },
            },
        },
    );
}

describe('swag-paypal-settings-banner-preview', () => {
    const store = Shopware.Store.get('swagPayPalSettings');

    beforeEach(() => {
        store.$reset();
        document.querySelectorAll('script[data-swag-paypal-banner-preview]').forEach((el) => el.remove());
        delete (window as any).paypal;
    });

    it('should be a Vue.js component', async () => {
        const wrapper = await createWrapper();

        expect(wrapper.vm).toBeTruthy();
    });

    it('should render the image area, skeletons, and sdk container', async () => {
        const wrapper = await createWrapper();

        expect(wrapper.find('.swag-paypal-settings-banner-preview__image-area').exists()).toBe(true);
        expect(wrapper.find('.swag-paypal-settings-banner-preview__skeletons').exists()).toBe(true);
        expect(wrapper.find('.swag-paypal-settings-banner-preview__sdk').exists()).toBe(true);
    });

    it('should default logoType to primary when not set in store', async () => {
        const wrapper = await createWrapper();

        expect(wrapper.vm.logoType).toBe('primary');
    });

    it('should default textColor to monochrome when not set in store', async () => {
        const wrapper = await createWrapper();

        expect(wrapper.vm.textColor).toBe('monochrome');
    });

    it('should default textSize to 12 when not set in store', async () => {
        const wrapper = await createWrapper();

        expect(wrapper.vm.textSize).toBe(12);
        expect(typeof wrapper.vm.textSize).toBe('number');
    });

    it('should return textSize as a number even when store contains a string value', async () => {
        store.setConfig(null, { 'SwagPayPal.settings.installmentBannerTextSize': '16' as unknown as number });
        const wrapper = await createWrapper();

        expect(wrapper.vm.textSize).toBe(16);
        expect(typeof wrapper.vm.textSize).toBe('number');
    });

    it('should reflect store changes to logoType', async () => {
        const wrapper = await createWrapper();

        store.set('SwagPayPal.settings.installmentBannerLogoType', 'alternative');
        await wrapper.vm.$nextTick();

        expect(wrapper.vm.logoType).toBe('alternative');
    });

    it('should reflect store changes to textColor', async () => {
        const wrapper = await createWrapper();

        store.set('SwagPayPal.settings.installmentBannerTextColor', 'white');
        await wrapper.vm.$nextTick();

        expect(wrapper.vm.textColor).toBe('white');
    });

    it('should use test as fallback client-id in loadSdk when clientId is empty', async () => {
        const appendSpy = jest.spyOn(document.head, 'appendChild');
        await createWrapper();

        const addedScripts = appendSpy.mock.calls
            .map(([el]) => el as HTMLElement)
            .filter((el) => el.tagName === 'SCRIPT' && el.hasAttribute('data-swag-paypal-banner-preview'));

        expect(addedScripts).toHaveLength(1);
        expect((addedScripts[0] as HTMLScriptElement).src).toContain('client-id=test');

        appendSpy.mockRestore();
    });

    it('should use real client-id in loadSdk when clientId is set', async () => {
        store.setConfig(null, {
            'SwagPayPal.settings.clientId': 'real-client-id',
            'SwagPayPal.settings.sandbox': false,
        });
        const appendSpy = jest.spyOn(document.head, 'appendChild');
        await createWrapper();

        const addedScripts = appendSpy.mock.calls
            .map(([el]) => el as HTMLElement)
            .filter((el) => el.tagName === 'SCRIPT' && el.hasAttribute('data-swag-paypal-banner-preview'));

        expect(addedScripts).toHaveLength(1);
        expect((addedScripts[0] as HTMLScriptElement).src).toContain('client-id=real-client-id');

        appendSpy.mockRestore();
    });

    it('should not call paypal.Messages if paypal is not loaded', async () => {
        const wrapper = await createWrapper();
        delete (window as any).paypal;

        await expect(() => wrapper.vm.renderPreview()).not.toThrow();
    });

    it('should call paypal.Messages with correct style parameters on renderPreview', async () => {
        store.setConfig(null, {
            'SwagPayPal.settings.installmentBannerLogoType': 'alternative',
            'SwagPayPal.settings.installmentBannerTextColor': 'white',
            'SwagPayPal.settings.installmentBannerTextSize': '16' as unknown as number,
        });

        const mockRender = jest.fn();
        const mockMessages = jest.fn(() => ({ render: mockRender }));
        (window as any).paypal = { Messages: mockMessages };

        const wrapper = await createWrapper();
        await wrapper.vm.renderPreview();

        expect(mockMessages).toHaveBeenCalledWith(expect.objectContaining({
            amount: 200,
            style: {
                layout: 'text',
                logo: { type: 'alternative' },
                text: { color: 'white', size: 16 },
            },
        }));
        expect(mockRender).toHaveBeenCalled();
    });

    it('should reuse existing script tag when sdk url has not changed', async () => {
        const appendSpy = jest.spyOn(document.head, 'appendChild');

        await createWrapper();
        const firstCallCount = appendSpy.mock.calls.filter(
            ([el]) => (el as HTMLElement).hasAttribute?.('data-swag-paypal-banner-preview'),
        ).length;

        await createWrapper();
        const secondCallCount = appendSpy.mock.calls.filter(
            ([el]) => (el as HTMLElement).hasAttribute?.('data-swag-paypal-banner-preview'),
        ).length;

        expect(firstCallCount).toBe(1);
        expect(secondCallCount).toBe(1);

        appendSpy.mockRestore();
    });
});
