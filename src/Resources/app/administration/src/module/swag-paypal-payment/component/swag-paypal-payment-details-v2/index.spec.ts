import { mount } from '@vue/test-utils';
import SwagPayPalPaymentDetailsV2 from '.';

Shopware.Component.register('swag-paypal-payment-details-v2', Promise.resolve(SwagPayPalPaymentDetailsV2));

function createPayPalOrder(captures: Array<Record<string, unknown>>) {
    return {
        id: 'paypal-order-id',
        intent: 'CAPTURE',
        status: 'COMPLETED',
        create_time: '2020-08-17T13:09:30Z',
        update_time: '2020-08-17T13:09:30Z',
        payer: {
            payer_id: 'payer-id',
        },
        purchase_units: [{
            amount: {
                currency_code: 'EUR',
                value: '100.00',
            },
            payments: {
                captures,
                refunds: [],
            },
        }],
    };
}

async function createWrapper(paypalOrder: Record<string, unknown>) {
    return mount(
        await Shopware.Component.build('swag-paypal-payment-details-v2') as typeof SwagPayPalPaymentDetailsV2,
        {
        props: {
            paypalOrder,
            orderTransaction: {
                customFields: {},
            },
        },
        global: {
            mocks: {
                $t: (key: string) => key,
            },
            stubs: {
                'mt-card': true,
                'sw-card-section': true,
                'swag-paypal-payment-actions-v2': true,
                'sw-description-list': true,
                'swag-paypal-pui-details': true,
                'sw-data-grid': true,
            },
        },
        },
    );
}

describe('swag-paypal-payment-details-v2', () => {
    it('should not treat declined captures as refundable', async () => {
        const wrapper = await createWrapper(createPayPalOrder([
            {
                id: 'declined-capture-id',
                status: 'DECLINED',
                amount: {
                    currency_code: 'EUR',
                    value: '100.00',
                },
                create_time: '2020-08-17T13:09:30Z',
                update_time: '2020-08-17T13:09:30Z',
            },
        ]));

        expect(wrapper.vm.refundableAmount).toBe(0);
        expect(wrapper.vm.payments).toHaveLength(1);
        expect(wrapper.vm.payments[0]).toMatchObject({
            id: 'declined-capture-id',
            status: 'DECLINED',
            total: '100.00 EUR',
        });
    });
});
