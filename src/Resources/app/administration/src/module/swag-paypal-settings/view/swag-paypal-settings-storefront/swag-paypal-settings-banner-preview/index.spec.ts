import { mount } from '@vue/test-utils';
import SwagPayPalSettingsBannerPreview from './index';

Shopware.Component.register('swag-paypal-settings-banner-preview', Promise.resolve(SwagPayPalSettingsBannerPreview));

async function createWrapper() {
    return mount(
        await Shopware.Component.build('swag-paypal-settings-banner-preview'),
        {
            global: {
                stubs: {
                    'sw-icon': true,
                    'mt-banner': true,
                    'paypal-message': true,
                },
            },
        },
    );
}

function flushPromises() {
    return new Promise(resolve => setTimeout(resolve, 0));
}

describe('swag-paypal-settings-banner-preview', () => {
    const store = Shopware.Store.get('swagPayPalSettings');

    beforeEach(async () => {
        store.$reset();
        document.querySelectorAll('script[data-swag-paypal-banner-preview-core]').forEach((el) => el.remove());
        // eslint-disable-next-line @typescript-eslint/no-explicit-any
        delete (window as any).paypal;
        // Mount with empty clientId to reset module-level sdkInitialized / messagesInstance
        const cleanup = await createWrapper();
        cleanup.unmount();
    });

    it('should be a Vue.js component', async () => {
        const wrapper = await createWrapper();

        expect(wrapper.vm).toBeTruthy();
    });

    it('should always render the image area and skeletons', async () => {
        const wrapper = await createWrapper();

        expect(wrapper.find('.swag-paypal-settings-banner-preview__image-area').exists()).toBe(true);
        expect(wrapper.find('.swag-paypal-settings-banner-preview__skeletons').exists()).toBe(true);
    });

    it('should show paypal-message element when clientId is set', async () => {
        store.setConfig(null, { 'SwagPayPal.settings.clientId': 'real-client-id' });
        const wrapper = await createWrapper();

        expect(wrapper.find('paypal-message-stub').exists()).toBe(true);
        expect(wrapper.find('mt-banner-stub').exists()).toBe(false);
    });

    it('should show empty banner when clientId is not set', async () => {
        const wrapper = await createWrapper();

        expect(wrapper.find('paypal-message-stub').exists()).toBe(false);
        expect(wrapper.find('mt-banner-stub').exists()).toBe(true);
    });

    describe('logoType computed', () => {
        it.each([
            ['primary', 'WORDMARK'],
            ['alternative', 'MONOGRAM'],
            ['inline', 'TEXT'],
            ['none', 'TEXT'],
        ])('maps %s → %s', async (raw, expected) => {
            store.setConfig(null, { 'SwagPayPal.settings.installmentBannerLogoType': raw });
            const wrapper = await createWrapper();

            expect(wrapper.vm.logoType).toBe(expected);
        });

        it('defaults to WORDMARK when not set', async () => {
            const wrapper = await createWrapper();

            expect(wrapper.vm.logoType).toBe('WORDMARK');
        });
    });

    describe('textColor computed', () => {
        it.each([
            ['black', 'BLACK'],
            ['white', 'WHITE'],
            ['monochrome', 'MONOCHROME'],
            ['grayscale', 'MONOCHROME'],
        ])('maps %s → %s', async (raw, expected) => {
            store.setConfig(null, { 'SwagPayPal.settings.installmentBannerTextColor': raw });
            const wrapper = await createWrapper();

            expect(wrapper.vm.textColor).toBe(expected);
        });

        it('defaults to MONOCHROME when not set', async () => {
            const wrapper = await createWrapper();

            expect(wrapper.vm.textColor).toBe('MONOCHROME');
        });
    });

    describe('textSize computed', () => {
        it('defaults to 12 as a number', async () => {
            const wrapper = await createWrapper();

            expect(wrapper.vm.textSize).toBe(12);
            expect(typeof wrapper.vm.textSize).toBe('number');
        });

        it('coerces string store value to number', async () => {
            store.setConfig(null, { 'SwagPayPal.settings.installmentBannerTextSize': '16' });
            const wrapper = await createWrapper();

            expect(wrapper.vm.textSize).toBe(16);
            expect(typeof wrapper.vm.textSize).toBe('number');
        });
    });

    describe('loadSdk', () => {
        it('does not append a script when clientId is empty', async () => {
            const appendSpy = jest.spyOn(document.head, 'appendChild');
            await createWrapper();

            const scripts = appendSpy.mock.calls
                .map(([el]) => el as HTMLElement)
                .filter((el) => el.tagName === 'SCRIPT' && el.hasAttribute('data-swag-paypal-banner-preview-core'));

            expect(scripts).toHaveLength(0);
            appendSpy.mockRestore();
        });

        it('appends core script with production URL when sandbox is false', async () => {
            store.setConfig(null, {
                'SwagPayPal.settings.clientId': 'real-client-id',
                'SwagPayPal.settings.sandbox': false,
            });
            const appendSpy = jest.spyOn(document.head, 'appendChild');
            await createWrapper();

            const scripts = appendSpy.mock.calls
                .map(([el]) => el as HTMLScriptElement)
                .filter((el) => el.tagName === 'SCRIPT' && el.hasAttribute('data-swag-paypal-banner-preview-core'));

            expect(scripts).toHaveLength(1);
            expect(scripts[0].src).toBe('https://www.paypal.com/web-sdk/v6/core');
            appendSpy.mockRestore();
        });

        it('appends core script with sandbox URL when sandbox is true', async () => {
            store.setConfig(null, {
                'SwagPayPal.settings.clientIdSandbox': 'sandbox-client-id',
                'SwagPayPal.settings.sandbox': true,
            });
            const appendSpy = jest.spyOn(document.head, 'appendChild');
            await createWrapper();

            const scripts = appendSpy.mock.calls
                .map(([el]) => el as HTMLScriptElement)
                .filter((el) => el.tagName === 'SCRIPT' && el.hasAttribute('data-swag-paypal-banner-preview-core'));

            expect(scripts).toHaveLength(1);
            expect(scripts[0].src).toBe('https://www.sandbox.paypal.com/web-sdk/v6/core');
            appendSpy.mockRestore();
        });

        it('does not append a second script when script tag already exists in DOM', async () => {
            store.setConfig(null, { 'SwagPayPal.settings.clientId': 'real-client-id' });
            const appendSpy = jest.spyOn(document.head, 'appendChild');

            await createWrapper();
            await createWrapper();

            const scripts = appendSpy.mock.calls
                .map(([el]) => el as HTMLElement)
                .filter((el) => el.tagName === 'SCRIPT' && el.hasAttribute('data-swag-paypal-banner-preview-core'));

            expect(scripts).toHaveLength(1);
            appendSpy.mockRestore();
        });
    });

    describe('initSdk / refreshPreview', () => {
        function setupSdkMock() {
            const mockSetContent = jest.fn();
            const mockFetchContent = jest.fn().mockResolvedValue(undefined);
            const mockCreatePayPalMessages = jest.fn().mockReturnValue({ fetchContent: mockFetchContent });
            const mockCreateInstance = jest.fn().mockResolvedValue({ createPayPalMessages: mockCreatePayPalMessages });
            // eslint-disable-next-line @typescript-eslint/no-explicit-any
            (window as any).paypal = { createInstance: mockCreateInstance };

            return { mockCreateInstance, mockCreatePayPalMessages, mockFetchContent, mockSetContent };
        }

        async function triggerScriptLoad(appendSpy: jest.SpyInstance) {
            const script = appendSpy.mock.calls
                .map(([el]) => el as HTMLScriptElement)
                .find((el) => el.hasAttribute('data-swag-paypal-banner-preview-core'));
            if (script?.onload) script.onload(new Event('load'));
            await flushPromises();
        }

        it('calls createInstance with clientId and paypal-messages component', async () => {
            store.setConfig(null, { 'SwagPayPal.settings.clientId': 'real-client-id' });
            const { mockCreateInstance } = setupSdkMock();
            const appendSpy = jest.spyOn(document.head, 'appendChild');

            await createWrapper();
            await triggerScriptLoad(appendSpy);

            expect(mockCreateInstance).toHaveBeenCalledWith({
                clientId: 'real-client-id',
                components: ['paypal-messages'],
            });
            appendSpy.mockRestore();
        });

        it('includes merchantId in createInstance when merchantPayerId is set', async () => {
            store.setConfig(null, {
                'SwagPayPal.settings.clientId': 'real-client-id',
                'SwagPayPal.settings.merchantPayerId': 'merchant-payer-id',
            });
            const { mockCreateInstance } = setupSdkMock();
            const appendSpy = jest.spyOn(document.head, 'appendChild');

            await createWrapper();
            await triggerScriptLoad(appendSpy);

            expect(mockCreateInstance).toHaveBeenCalledWith({
                clientId: 'real-client-id',
                merchantId: 'merchant-payer-id',
                components: ['paypal-messages'],
            });
            appendSpy.mockRestore();
        });

        it('omits merchantId from createInstance when merchantPayerId is not set', async () => {
            store.setConfig(null, { 'SwagPayPal.settings.clientId': 'real-client-id' });
            const { mockCreateInstance } = setupSdkMock();
            const appendSpy = jest.spyOn(document.head, 'appendChild');

            await createWrapper();
            await triggerScriptLoad(appendSpy);

            expect(mockCreateInstance).toHaveBeenCalledWith({
                clientId: 'real-client-id',
                components: ['paypal-messages'],
            });
            appendSpy.mockRestore();
        });

        it('calls createPayPalMessages with currencyCode USD', async () => {
            store.setConfig(null, { 'SwagPayPal.settings.clientId': 'real-client-id' });
            const { mockCreatePayPalMessages } = setupSdkMock();
            const appendSpy = jest.spyOn(document.head, 'appendChild');

            await createWrapper();
            await triggerScriptLoad(appendSpy);

            expect(mockCreatePayPalMessages).toHaveBeenCalledWith({ currencyCode: 'USD' });
            appendSpy.mockRestore();
        });

        it('calls fetchContent with mapped logoType, logoPosition, and textColor', async () => {
            store.setConfig(null, {
                'SwagPayPal.settings.clientId': 'real-client-id',
                'SwagPayPal.settings.installmentBannerLogoType': 'alternative',
                'SwagPayPal.settings.installmentBannerTextColor': 'black',
                'SwagPayPal.settings.installmentBannerTextSize': '16',
            });
            const { mockFetchContent } = setupSdkMock();
            const appendSpy = jest.spyOn(document.head, 'appendChild');

            await createWrapper();
            await triggerScriptLoad(appendSpy);

            expect(mockFetchContent).toHaveBeenCalledWith(expect.objectContaining({
                amount: '200',
                currencyCode: 'USD',
                buyerCountry: 'US',
                logoType: 'MONOGRAM',
                logoPosition: 'LEFT',
                textColor: 'BLACK',
            }));
            appendSpy.mockRestore();
        });

        it('uses logoPosition INLINE for TEXT logoType', async () => {
            store.setConfig(null, {
                'SwagPayPal.settings.clientId': 'real-client-id',
                'SwagPayPal.settings.installmentBannerLogoType': 'inline',
            });
            const { mockFetchContent } = setupSdkMock();
            const appendSpy = jest.spyOn(document.head, 'appendChild');

            await createWrapper();
            await triggerScriptLoad(appendSpy);

            expect(mockFetchContent).toHaveBeenCalledWith(expect.objectContaining({
                logoType: 'TEXT',
                logoPosition: 'INLINE',
            }));
            appendSpy.mockRestore();
        });

        it('maps grayscale textColor to MONOCHROME in fetchContent', async () => {
            store.setConfig(null, {
                'SwagPayPal.settings.clientId': 'real-client-id',
                'SwagPayPal.settings.installmentBannerTextColor': 'grayscale',
            });
            const { mockFetchContent } = setupSdkMock();
            const appendSpy = jest.spyOn(document.head, 'appendChild');

            await createWrapper();
            await triggerScriptLoad(appendSpy);

            expect(mockFetchContent).toHaveBeenCalledWith(expect.objectContaining({
                textColor: 'MONOCHROME',
            }));
            appendSpy.mockRestore();
        });

        it('does not throw when window.paypal is not available', async () => {
            store.setConfig(null, { 'SwagPayPal.settings.clientId': 'real-client-id' });
            // eslint-disable-next-line @typescript-eslint/no-explicit-any
            delete (window as any).paypal;
            const appendSpy = jest.spyOn(document.head, 'appendChild');

            await expect(
                createWrapper().then(() => triggerScriptLoad(appendSpy)),
            ).resolves.toBeUndefined();

            appendSpy.mockRestore();
        });

        it('sets sdkError when createInstance rejects', async () => {
            store.setConfig(null, { 'SwagPayPal.settings.clientId': 'bad-client-id' });
            const mockCreateInstance = jest.fn().mockRejectedValue(new Error('SdkInitError'));
            // eslint-disable-next-line @typescript-eslint/no-explicit-any
            (window as any).paypal = { createInstance: mockCreateInstance };
            const appendSpy = jest.spyOn(document.head, 'appendChild');

            const wrapper = await createWrapper();
            await triggerScriptLoad(appendSpy);

            expect(wrapper.vm.sdkError).toBe(true);
            expect(wrapper.find('[variant="warning"]').exists()).toBe(true);
            appendSpy.mockRestore();
        });

        it('sets noOffersAvailable when fetchContent rejects', async () => {
            store.setConfig(null, { 'SwagPayPal.settings.clientId': 'real-client-id' });
            const mockFetchContent = jest.fn().mockRejectedValue(new Error('No offers'));
            const mockCreatePayPalMessages = jest.fn().mockReturnValue({ fetchContent: mockFetchContent });
            const mockCreateInstance = jest.fn().mockResolvedValue({ createPayPalMessages: mockCreatePayPalMessages });
            // eslint-disable-next-line @typescript-eslint/no-explicit-any
            (window as any).paypal = { createInstance: mockCreateInstance };
            const appendSpy = jest.spyOn(document.head, 'appendChild');

            const wrapper = await createWrapper();
            await triggerScriptLoad(appendSpy);

            expect(wrapper.vm.noOffersAvailable).toBe(true);
            expect(wrapper.find('[variant="info"]').exists()).toBe(true);
            appendSpy.mockRestore();
        });

        it('clears noOffersAvailable when a subsequent fetchContent succeeds', async () => {
            store.setConfig(null, { 'SwagPayPal.settings.clientId': 'real-client-id' });
            const mockSetContent = jest.fn();
            const mockFetchContent = jest.fn()
                .mockRejectedValueOnce(new Error('No offers'))
                .mockImplementation(({ onReady }: { onReady: (c: unknown) => void }) => { onReady({}); return Promise.resolve(); });
            const mockCreatePayPalMessages = jest.fn().mockReturnValue({ fetchContent: mockFetchContent });
            const mockCreateInstance = jest.fn().mockResolvedValue({ createPayPalMessages: mockCreatePayPalMessages });
            // eslint-disable-next-line @typescript-eslint/no-explicit-any
            (window as any).paypal = { createInstance: mockCreateInstance };
            const appendSpy = jest.spyOn(document.head, 'appendChild');

            const wrapper = await createWrapper();
            await triggerScriptLoad(appendSpy);

            // First call failed
            expect(wrapper.vm.noOffersAvailable).toBe(true);

            // Simulate a setting change that triggers refreshPreview again
            const el = wrapper.find('paypal-message-stub').element as HTMLElement & { setContent: typeof mockSetContent };
            el.setContent = mockSetContent;
            await wrapper.vm.refreshPreview();
            await flushPromises();

            expect(wrapper.vm.noOffersAvailable).toBe(false);
            appendSpy.mockRestore();
        });
    });
});
