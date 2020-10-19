import './component/swag-paypal-payment-actions';
import './component/swag-paypal-payment-details-v1';
import './component/swag-paypal-payment-details-v2';
import './component/swag-paypal-text-field';
import './extension/sw-order';
import './page/swag-paypal-payment-detail';

import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';

const { Module } = Shopware;

Module.register('swag-paypal-payment', {
    type: 'plugin',
    name: 'SwagPayPal',
    title: 'swag-paypal-payment.general.title',
    description: 'swag-paypal-payment.general.descriptionTextModule',
    version: '1.0.0',
    targetVersion: '1.0.0',
    color: '#2b52ff',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    routeMiddleware(next, currentRoute) {
        if (currentRoute.name === 'sw.order.detail') {
            currentRoute.children.push({
                component: 'swag-paypal-payment-detail',
                name: 'swag.paypal.payment.detail',
                isChildren: true,
                path: '/sw/order/paypal/detail/:id',
                meta: {
                    parentPath: 'sw.order.index',
                    privilege: 'order.viewer'
                }
            });
        }
        next(currentRoute);
    }
});
