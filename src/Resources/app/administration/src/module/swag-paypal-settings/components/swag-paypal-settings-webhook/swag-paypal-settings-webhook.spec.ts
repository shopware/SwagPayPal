import { mount } from '@vue/test-utils';
import SwagPayPalSettingsWebhook from '.';

Shopware.Component.register('swag-paypal-settings-webhook', Promise.resolve(SwagPayPalSettingsWebhook));

const APP_URL_SOURCES = [
    { source: 'administration', host: 'shop.example' },
    { source: 'app_url', host: 'app.example' },
];

async function createWrapper(status: () => Promise<unknown> = () => Promise.resolve({ result: null })) {
    return mount(
        await Shopware.Component.build('swag-paypal-settings-webhook') as typeof SwagPayPalSettingsWebhook,
        {
            global: {
                mocks: { $tc: (key: string) => key },
                provide: {
                    acl: { can: () => true },
                    SwagPayPalWebhookService: {
                        status: jest.fn(status),
                        register: jest.fn(() => Promise.resolve()),
                    },
                    SwagPayPalSettingsService: {},
                    settingsStoreSavingSettings: Shopware.Vue.ref('none'),
                },
                stubs: {
                    'sw-label': await wrapTestComponent('sw-label', { sync: true }),
                    'sw-color-badge': await wrapTestComponent('sw-color-badge', { sync: true }),
                },
            },
        },
    );
}

describe('swag-paypal-settings-webhook', () => {
    it('should be a Vue.js component', async () => {
        const wrapper = await createWrapper();

        expect(wrapper.vm).toBeTruthy();
    });

    it('should fetch status on creation', async () => {
        const wrapper = await createWrapper();

        expect(wrapper.vm.SwagPayPalWebhookService.status).toHaveBeenCalled();
    });

    it('should pick correct status variant', async () => {
        const wrapper = await createWrapper();

        wrapper.vm.allWebhookStatus.null = 'valid';
        expect(wrapper.vm.webhookStatusVariant).toBe('success');

        wrapper.vm.allWebhookStatus.null = 'missing';
        expect(wrapper.vm.webhookStatusVariant).toBe('danger');

        wrapper.vm.allWebhookStatus.null = 'invalid';
        expect(wrapper.vm.webhookStatusVariant).toBe('warning');

        wrapper.vm.allWebhookStatus.null = '';
        expect(wrapper.vm.webhookStatusVariant).toBe('neutral');

        wrapper.vm.allWebhookStatus.null = undefined;
        expect(wrapper.vm.webhookStatusVariant).toBe('neutral');
    });

    it('should allow refresh', async () => {
        const wrapper = await createWrapper();

        wrapper.vm.allWebhookStatus.null = 'valid';
        expect(wrapper.vm.allowRefresh).toBe(false);

        wrapper.vm.allWebhookStatus.null = 'missing';
        expect(wrapper.vm.allowRefresh).toBe(true);

        wrapper.vm.allWebhookStatus.null = 'invalid';
        expect(wrapper.vm.allowRefresh).toBe(true);

        wrapper.vm.allWebhookStatus.null = '';
        expect(wrapper.vm.allowRefresh).toBe(false);

        wrapper.vm.allWebhookStatus.null = undefined;
        expect(wrapper.vm.allowRefresh).toBe(false);
    });

    it('should have correct status label', async () => {
        const wrapper = await createWrapper();
        await flushPromises();

        wrapper.vm.allWebhookStatus.null = 'valid';
        expect(wrapper.vm.webhookStatusLabel).toBe('swag-paypal-settings.webhook.status.valid');

        wrapper.vm.allWebhookStatus.null = 'missing';
        expect(wrapper.vm.webhookStatusLabel).toBe('swag-paypal-settings.webhook.status.missing');

        wrapper.vm.allWebhookStatus.null = 'invalid';
        expect(wrapper.vm.webhookStatusLabel).toBe('swag-paypal-settings.webhook.status.invalid');

        wrapper.vm.allWebhookStatus.null = '';
        expect(wrapper.vm.webhookStatusLabel).toBe('swag-paypal-settings.webhook.status.unknown');

        wrapper.vm.allWebhookStatus.null = undefined;
        expect(wrapper.vm.webhookStatusLabel).toBe('swag-paypal-settings.webhook.status.unknown');
    });

    it('should show loading while fetching and unknown when the status could not be fetched', async () => {
        const wrapper = await createWrapper();
        await flushPromises();

        expect(wrapper.vm.webhookStatusLabel).toBe('swag-paypal-settings.webhook.status.unknown');

        wrapper.vm.SwagPayPalWebhookService.status = jest.fn(() => new Promise(() => {}));
        wrapper.vm.fetchWebhookStatus(null);

        expect(wrapper.vm.status).toBe('fetching');
        expect(wrapper.vm.webhookStatusLabel).toBe('swag-paypal-settings.webhook.status.loading');

        wrapper.vm.status = 'none';
        wrapper.vm.createNotificationError = jest.fn();
        wrapper.vm.SwagPayPalWebhookService.status = jest.fn(() => Promise.reject({ response: {} }));
        wrapper.vm.fetchWebhookStatus(null);

        expect(wrapper.vm.webhookStatusLabel).toBe('swag-paypal-settings.webhook.status.loading');

        await flushPromises();

        expect(wrapper.vm.createNotificationError).toHaveBeenCalledTimes(1);
        expect(wrapper.vm.webhookStatusLabel).toBe('swag-paypal-settings.webhook.status.unknown');
    });

    it('should reset the loading state when fetching fails and remember the failure', async () => {
        const wrapper = await createWrapper();
        await flushPromises();

        wrapper.vm.createNotificationError = jest.fn();
        const status = jest.fn(() => Promise.reject({ response: {} }));
        wrapper.vm.SwagPayPalWebhookService.status = status;

        wrapper.vm.fetchWebhookStatus(null);
        await flushPromises();

        expect(wrapper.vm.status).toBe('none');

        wrapper.vm.fetchWebhookStatus(null);
        await flushPromises();

        expect(status).toHaveBeenCalledTimes(1);

        // @ts-expect-error - property does exist, it got injected
        wrapper.vm.settingsStoreSavingSettings = 'success';
        await flushPromises();

        expect(status).toHaveBeenCalledTimes(2);
    });

    it('should fetch webhook status', async () => {
        const wrapper = await createWrapper();

        const spyStatus = jest.spyOn(wrapper.vm.SwagPayPalWebhookService, 'status');

        wrapper.vm.fetchWebhookStatus(null);

        expect(wrapper.vm.status).toBe('fetching');
        expect(spyStatus).toHaveBeenCalled();

        await wrapper.vm.$nextTick();

        expect(wrapper.vm.status).toBe('none');
    });

    it('should refresh webhook', async () => {
        const wrapper = await createWrapper();

        const spyStatus = jest.spyOn(wrapper.vm.SwagPayPalWebhookService, 'status');
        const spyRegister = jest.spyOn(wrapper.vm.SwagPayPalWebhookService, 'register');

        wrapper.vm.onRefreshWebhook();

        expect(wrapper.vm.status).toBe('refreshing');
        expect(spyRegister).toHaveBeenCalled();

        await flushPromises();

        expect(wrapper.vm.status).toBe('none');

        await wrapper.vm.$nextTick();

        expect(spyStatus).toHaveBeenCalled();
    });

    describe('with the APP_URL as a source', () => {
        const SELECT = '.swag-paypal-settings-webhook__url-source';

        const createWrapperWithAppUrl = (urlSources: unknown[] = APP_URL_SOURCES) => {
            return createWrapper(() => Promise.resolve({ result: 'invalid', urlSources }));
        };

        it('should offer both sources with their hosts', async () => {
            const wrapper = await createWrapperWithAppUrl();
            await flushPromises();

            expect(wrapper.find(SELECT).exists()).toBe(true);
            expect(wrapper.vm.urlSourceOptions).toEqual([
                { value: 'administration', label: 'swag-paypal-settings.webhook.urlSource.administration' },
                { value: 'app_url', label: 'swag-paypal-settings.webhook.urlSource.app_url' },
            ]);
        });

        it.each([
            ['without an APP_URL', [APP_URL_SOURCES[0]]],
            ['without sources', []],
        ])('should hide the select %s', async (_, urlSources) => {
            const wrapper = await createWrapperWithAppUrl(urlSources);
            await flushPromises();

            expect(wrapper.find(SELECT).exists()).toBe(false);
        });

        it('should hide the select without sources in the status', async () => {
            const wrapper = await createWrapper(() => Promise.resolve({ result: 'invalid' }));
            await flushPromises();

            expect(wrapper.find(SELECT).exists()).toBe(false);
        });

        it('should refresh with the URL of the Administration by default', async () => {
            const wrapper = await createWrapperWithAppUrl();
            await flushPromises();

            await wrapper.vm.onRefreshWebhook();

            expect(wrapper.vm.SwagPayPalWebhookService.register).toHaveBeenCalledWith(null, 'administration');
        });

        it('should refresh with the selected source', async () => {
            const wrapper = await createWrapperWithAppUrl();
            await flushPromises();

            wrapper.vm.urlSource = 'app_url';
            await wrapper.vm.onRefreshWebhook();

            expect(wrapper.vm.SwagPayPalWebhookService.register).toHaveBeenCalledWith(null, 'app_url');
        });

        it('should ignore a selected source that is not offered', async () => {
            const wrapper = await createWrapperWithAppUrl([APP_URL_SOURCES[0]]);
            await flushPromises();

            wrapper.vm.urlSource = 'app_url';
            await wrapper.vm.onRefreshWebhook();

            expect(wrapper.vm.SwagPayPalWebhookService.register).toHaveBeenCalledWith(null, undefined);
        });

        it('should disable the select like the button', async () => {
            const wrapper = await createWrapperWithAppUrl();
            await flushPromises();

            expect(wrapper.findComponent<{ props: () => { disabled: boolean } }>(SELECT).props('disabled')).toBe(false);

            wrapper.vm.allWebhookStatus.null = 'valid';
            await flushPromises();

            expect(wrapper.vm.canRefresh).toBe(false);
            expect(wrapper.findComponent<{ props: () => { disabled: boolean } }>(SELECT).props('disabled')).toBe(true);
        });
    });

    it('should refresh webhook with error', async () => {
        const wrapper = await createWrapper();

        wrapper.vm.createNotificationError = jest.fn();

        wrapper.vm.SwagPayPalWebhookService.register = jest.fn(() => Promise.reject({ response: {} }));

        wrapper.vm.onRefreshWebhook();

        await wrapper.vm.$nextTick();

        expect(wrapper.vm.status).toBe('refreshing');
        expect(wrapper.vm.createNotificationError).toHaveBeenCalled();
    });

    it('should fetch webhook status on save', async () => {
        const wrapper = await createWrapper();

        const spyFetch = jest.spyOn(wrapper.vm, 'fetchWebhookStatus');

        // @ts-expect-error - property does exist, it got injected
        wrapper.vm.settingsStoreSavingSettings = 'success';

        await wrapper.vm.$nextTick();

        expect(spyFetch).toHaveBeenCalled();
    });
});
