import { mount } from '@vue/test-utils';
import SwagPayPalPaymentDetailsV2 from '.';

Shopware.Component.register('swag-paypal-payment-details-v2', Promise.resolve(SwagPayPalPaymentDetailsV2));

function createPayPalOrder(captures: Array<Record<string, unknown>>, refunds: Array<Record<string, unknown>> = []) {
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
                refunds,
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

    it.each([
        ['COMPLETED', 2.15],
        ['PENDING', 2.15],
        ['FAILED', 52.97],
        ['CANCELLED', 52.97],
    ])('should compute the refundable amount with a %s refund', async (refundStatus, expectedRefundableAmount) => {
        const wrapper = await createWrapper(createPayPalOrder(
            [{
                id: 'capture-id',
                status: 'COMPLETED',
                amount: {
                    currency_code: 'EUR',
                    value: '52.97',
                },
                create_time: '2020-08-17T13:09:30Z',
                update_time: '2020-08-17T13:09:30Z',
            }],
            [{
                id: 'refund-id',
                status: refundStatus,
                amount: {
                    currency_code: 'EUR',
                    value: '50.82',
                },
                create_time: '2020-08-18T13:09:30Z',
                update_time: '2020-08-18T13:09:30Z',
            }],
        ));

        expect(wrapper.vm.refundableAmount).toBe(expectedRefundableAmount);
        expect(wrapper.vm.payments).toHaveLength(2);
        expect(wrapper.vm.payments[1]).toMatchObject({
            id: 'refund-id',
            status: refundStatus,
        });
    });

    it('should deduct only the completed refund when another one failed', async () => {
        const wrapper = await createWrapper(createPayPalOrder(
            [{
                id: 'capture-id',
                status: 'COMPLETED',
                amount: {
                    currency_code: 'EUR',
                    value: '52.97',
                },
                create_time: '2020-08-17T13:09:30Z',
                update_time: '2020-08-17T13:09:30Z',
            }],
            [{
                id: 'failed-refund-id',
                status: 'FAILED',
                amount: {
                    currency_code: 'EUR',
                    value: '50.82',
                },
                create_time: '2020-08-18T13:09:30Z',
                update_time: '2020-08-18T13:09:30Z',
            }, {
                id: 'completed-refund-id',
                status: 'COMPLETED',
                amount: {
                    currency_code: 'EUR',
                    value: '10.00',
                },
                create_time: '2020-08-19T13:09:30Z',
                update_time: '2020-08-19T13:09:30Z',
            }],
        ));

        expect(wrapper.vm.refundableAmount).toBe(42.97);
    });
});
