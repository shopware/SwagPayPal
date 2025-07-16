import { mount } from '@vue/test-utils';
import SwSalesChannelModal from 'src/module/sw-sales-channel/component/sw-sales-channel-modal';
import SwSalesChannelModalExtension from '.';
import { SwagPayPalDefaults } from "SwagPayPal/defaults";

Shopware.Component.register('sw-sales-channel-modal', Promise.resolve(SwSalesChannelModal));

Shopware.Component.override(
    'sw-sales-channel-modal',
    Shopware.Component.extend(
        'swag-paypal-agent-commerce-sales-channel-modal',
        'sw-sales-channel-modal',
        Promise.resolve(SwSalesChannelModalExtension),
    ),
);

async function createWrapper() {
    return mount(await Shopware.Component.build('swag-paypal-agent-commerce-sales-channel-modal') as typeof SwSalesChannelModalExtension, {
        global: {
            provide: {
                repositoryFactory: {
                    create: () => {
                        return {
                            search: () => {
                                return Promise.resolve({ total: 1 });
                            },
                            save: () => {
                                return Promise.resolve();
                            },
                            get: () => {
                                return Promise.resolve();
                            },
                        };
                    },
                },
            },
            stubs: {
                'sw-modal': true,
                'sw-button': true,
                'sw-icon': true,
                'sw-sales-channel-listing': true,
                'sw-sales-channel-modal-grid': true,
                'sw-sales-channel-modal-detail': true,
            },
            mocks: {
                $router: {
                    push: jest.fn(),
                },
                $tc: (key: string) => key,
                onCloseModal: () => {},
            },
        },
    });
}

describe('sw-sales-channel-modal', () => {
    afterEach(async () => {
        jest.clearAllMocks();
    });

    it('should be a Vue component', async () => {
        const wrapper = await createWrapper();

        expect(wrapper.vm).toBeTruthy();
    });

    it('should push to correct route on save', async () => {
        const wrapper = await createWrapper();

        wrapper.vm.onAddChannel(SwagPayPalDefaults.agentCommerceTypeId);

        expect(wrapper.vm.$router.push).toHaveBeenCalledWith({
            name: 'swag.paypal.agent.commerce.create',
            params: { typeId: SwagPayPalDefaults.agentCommerceTypeId },
        });
    });

    it('should not push to route if no id is provided', async () => {
        const wrapper = await createWrapper();

        wrapper.vm.onAddChannel(null);

        expect(wrapper.vm.$router.push).not.toHaveBeenCalled();
    });

    it('should not push to route for non-agent commerce type', async () => {
        const wrapper = await createWrapper();

        wrapper.vm.onAddChannel(Shopware.Defaults.productComparisonTypeId);

        expect(wrapper.vm.$router.push).toHaveBeenCalledWith({
            name: 'sw.sales.channel.create',
            params: { typeId: Shopware.Defaults.productComparisonTypeId },
        });
    });

    it('should identify agent commerce sales channel as product comparison type', async () => {
        const wrapper = await createWrapper();

        const isProductComparison = wrapper.vm.isProductComparisonSalesChannelType(SwagPayPalDefaults.agentCommerceTypeId);

        expect(isProductComparison).toBeTruthy();
    });

    it ('should identify product comparison sales channel as product comparison type', async () => {
        const wrapper = await createWrapper();

        const isProductComparison = wrapper.vm.isProductComparisonSalesChannelType(Shopware.Defaults.productComparisonTypeId);

        expect(isProductComparison).toBeTruthy();
    });

    it('should identify non-agent commerce sales channel as not product comparison type', async () => {
        const wrapper = await createWrapper();

        const isProductComparison = wrapper.vm.isProductComparisonSalesChannelType('some-other-type-id');

        expect(isProductComparison).toBeFalsy();
    });
});
