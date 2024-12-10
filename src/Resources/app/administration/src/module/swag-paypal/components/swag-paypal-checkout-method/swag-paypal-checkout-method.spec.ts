import { mount } from '@vue/test-utils';
import SwagPayPalCheckoutMethod from '.';

Shopware.Component.register('swag-paypal-checkout-method', Promise.resolve(SwagPayPalCheckoutMethod));
Shopware.Component.register('swag-paypal-checkout-domain-association', () => import('../swag-paypal-checkout-domain-association'));

async function createWrapper(customOptions = {}) {
    const options = {
        global: {
            mocks: {
                $tc: (key: string) => key,
                $te: (key: string) => key,
            },
            provide: {
                acl: {
                    can: () => true,
                },
            },
            stubs: {
                'swag-paypal-checkout-domain-association':
                    await Shopware.Component.build('swag-paypal-checkout-domain-association'),
                'sw-alert':
                    await wrapTestComponent('sw-alert', { sync: true }),
                'sw-alert-deprecated':
                    await wrapTestComponent('sw-alert-deprecated', { sync: true }),
            },
        },

        props: {
            paymentMethod: {
                name: 'Apple Pay',
                formattedHandlerIdentifier: 'handler_swag_applepayhandler',
                translated: {
                    name: 'Apple Pay',
                },
            },
        },
    };
    return mount(
        await Shopware.Component.build('swag-paypal-checkout-method') as typeof SwagPayPalCheckoutMethod,
        Shopware.Utils.object.mergeWith(options, customOptions),
    );
}
describe('Paypal Domain Association Component', () => {
    it('should be a Vue.js component', async () => {
        const wrapper = await createWrapper();

        await flushPromises();

        expect(wrapper.vm).toBeTruthy();
    });

    it('should be false when a paymentMethod is Apple Pay and its not active', async () => {
        const wrapper = await createWrapper({
            props: {
                paymentMethod: {
                    active: true,
                },
            },
        });

        await flushPromises();

        let alert = wrapper.find('.swag-plugin-apple-pay-warning');

        expect(alert.exists()).toBeTruthy();

        await wrapper.setProps({
            paymentMethod: {
                active: false,
                name: 'Apple Pay',
                formattedHandlerIdentifier: 'handler_swag_applepayhandler',
                translated: {
                    name: 'Apple Pay',
                },
            } as TEntity<'payment_method'>,
        });

        await flushPromises();

        alert = wrapper.find('.swag-plugin-apple-pay-warning');

        expect(alert.exists()).toBeFalsy();
    });

    it('should be ture when a paymentMethod is Apple Pay and its active', async () => {
        const wrapper = await createWrapper({
            props: {
                paymentMethod: {
                    active: false,
                },
            },
        });

        await flushPromises();

        let alert = wrapper.find('.swag-plugin-apple-pay-warning');

        expect(alert.exists()).toBeFalsy();

        await wrapper.setProps({
            paymentMethod: {
                active: true,
                name: 'Apple Pay',
                formattedHandlerIdentifier: 'handler_swag_applepayhandler',
                translated: {
                    name: 'Apple Pay',
                },
            } as TEntity<'payment_method'>,
        });

        await flushPromises();

        alert = wrapper.find('.swag-plugin-apple-pay-warning');

        expect(alert.exists()).toBeTruthy();
    });

    it('should hide alert by button click', async () => {
        const wrapper = await createWrapper({
            props: {
                paymentMethod: {
                    active: true,
                },
            },
        });

        await flushPromises();

        expect(wrapper.find('.swag-plugin-apple-pay-warning').exists()).toBeTruthy();

        const button = wrapper.find('.sw-alert__close');
        await button.trigger('click');

        await flushPromises();

        expect(wrapper.find('.swag-plugin-apple-pay-warning').exists()).toBeFalsy();
    });
});
