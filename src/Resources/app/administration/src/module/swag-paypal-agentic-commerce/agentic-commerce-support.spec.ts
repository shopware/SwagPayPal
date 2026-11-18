import { isAgenticCommerceSupported } from './agentic-commerce-support';

describe('agentic-commerce-support', () => {
    afterEach(() => {
        Shopware.State.commit('context/addAppConfigValue', { key: 'version', value: null });
    });

    it.each([
        { version: '6.6.0.0', supported: false },
        { version: '6.6.8.2', supported: false },
        { version: '6.6.9.0', supported: true },
        { version: '6.6.10.4', supported: true },
        { version: '6.6.9999999.9999999-dev', supported: true },
        { version: null, supported: true },
    ])('should return $supported for Shopware $version', ({ version, supported }) => {
        Shopware.State.commit('context/addAppConfigValue', { key: 'version', value: version });

        expect(isAgenticCommerceSupported()).toBe(supported);
    });
});
