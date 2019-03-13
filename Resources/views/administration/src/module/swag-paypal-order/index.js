import { Module } from 'src/core/shopware';
import './extension/sw-order';
import orderDetail from './page/swag-paypal-order-detail';

Module.register('swag-paypal-order', {
    type: 'plugin',
    name: 'SwagPayPal',
    description: 'swag-paypal.general.descriptionTextModule',
    version: '1.0.0',
    targetVersion: '1.0.0',

    routes: {
        detail: {
            component: orderDetail,
            path: 'detail/:id',
            meta: {
                parentPath: 'sw.order.detail.base'
            }
        }
    }
});
