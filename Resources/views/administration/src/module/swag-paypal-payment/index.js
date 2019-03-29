import { Module } from 'src/core/shopware';
import './extension/sw-order';
import paymentDetail from './page/swag-paypal-payment-detail';
import paymentActions from '../../app/component/swag-paypal-payment-actions';
import captureAction from '../../app/component/swag-paypal-payment-actions/extensions/swag-paypal-payment-action-capture';
import refundAction from '../../app/component/swag-paypal-payment-actions/extensions/swag-paypal-payment-action-refund';
import voidAction from '../../app/component/swag-paypal-payment-actions/extensions/swag-paypal-payment-action-void';
import deDE from './snippet/de_DE.json';
import enGB from './snippet/en_GB.json';

Module.register('swag-paypal-payment', {
    type: 'plugin',
    name: 'SwagPayPal',
    description: 'swag-paypal-payment.general.descriptionTextModule',
    version: '1.0.0',
    targetVersion: '1.0.0',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    routes: {
        detail: {
            components: {
                default: paymentDetail,
                paymentActions: paymentActions,
                authorizeAction: captureAction,
                refundAction: refundAction,
                voidAction: voidAction
            },
            path: 'detail/:id',
            meta: {
                parentPath: 'sw.order.detail.base'
            }
        }
    }
});
