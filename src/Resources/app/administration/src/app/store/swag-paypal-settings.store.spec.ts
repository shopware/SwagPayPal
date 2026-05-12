import SettingsFixture from './settings.fixture';
import './swag-paypal-settings.store';

describe('swag-paypal-settings.store', () => {
    const store = Shopware.Store.get('swagPayPalSettings');

    it('shoud be a pinia store', () => {
        expect(store.$id).toBe('swagPayPalSettings');
    });

    it('should have correct default state', () => {
        // state
        expect(store.salesChannel).toBeNull();
        expect(store.allConfigs).toStrictEqual({});

        // actions
        expect(store.hasConfig(null)).toBe(false);

        // getters
        expect(store.isLoading).toBe(true);
        expect(store.isSandbox).toBe(false);
        expect(store.root).toStrictEqual({});
        expect(store.actual).toStrictEqual({});
    });

    it('should have correct root state', () => {
        store.setConfig(null, SettingsFixture.Default);

        // state
        expect(store.salesChannel).toBeNull();
        expect(store.allConfigs).toStrictEqual({ null: SettingsFixture.Default });

        // actions
        expect(store.hasConfig(null)).toBe(true);

        // getters
        expect(store.isLoading).toBe(false);
        expect(store.isSandbox).toBe(false);
        expect(store.root).toStrictEqual(SettingsFixture.Default);
        expect(store.actual).toStrictEqual(SettingsFixture.Default);
    });

    it('should have correct actual state', () => {
        const actual = { 'SwagPayPal.settings.sandbox': true };

        store.setConfig(null, SettingsFixture.Default);
        store.salesChannel = 'some-other-id';
        store.setConfig('some-other-id', actual);

        // state
        expect(store.salesChannel).toBe('some-other-id');
        expect(store.allConfigs).toStrictEqual({
            null: SettingsFixture.Default,
            'some-other-id': actual,
        });

        // actions
        expect(store.hasConfig(null)).toBe(true);
        expect(store.hasConfig('some-other-id')).toBe(true);

        // getters
        expect(store.isLoading).toBe(false);
        expect(store.isSandbox).toBe(true);
        expect(store.root).toStrictEqual(SettingsFixture.Default);
        expect(store.actual).toStrictEqual(actual);
    });

    it('should have inherit correctly with root value', () => {
        store.setConfig(null, SettingsFixture.Default);
        store.salesChannel = 'some-other-id';
        store.setConfig('some-other-id', {});

        const key = 'SwagPayPal.settings.intent';

        expect(store.get(key)).toBe('CAPTURE');
        expect(store.getRoot(key)).toBe('CAPTURE');
        expect(store.getActual(key)).toBeUndefined();

        store.set(key, 'AUTHORIZE');
        expect(store.get(key)).toBe('AUTHORIZE');
        expect(store.getRoot(key)).toBe('CAPTURE');
        expect(store.getActual(key)).toBe('AUTHORIZE');
    });

    it('should have inherit correctly without root value', () => {
        store.setConfig(null, SettingsFixture.Default);
        store.salesChannel = 'some-other-id';
        store.setConfig('some-other-id', {});

        const key = 'SwagPayPal.settings.clientId';

        expect(store.get(key)).toBeUndefined();
        expect(store.getRoot(key)).toBeUndefined();
        expect(store.getActual(key)).toBeUndefined();

        store.set(key, 'some-client-id');
        expect(store.get(key)).toBe('some-client-id');
        expect(store.getRoot(key)).toBeUndefined();
        expect(store.getActual(key)).toBe('some-client-id');
    });

    it('should have inherit correctly with root NULL value', () => {
        store.setConfig(null, SettingsFixture.Default);
        store.salesChannel = 'some-other-id';
        store.setConfig('some-other-id', {});

        const key = 'SwagPayPal.settings.crossBorderBuyerCountry';

        expect(store.get(key)).toBeNull();
        expect(store.getRoot(key)).toBeNull();
        expect(store.getActual(key)).toBeUndefined();

        store.set(key, 'de-DE');
        expect(store.get(key)).toBe('de-DE');
        expect(store.getRoot(key)).toBeNull();
        expect(store.getActual(key)).toBe('de-DE');
    });
});
