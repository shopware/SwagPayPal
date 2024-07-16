import './acl';
import './page/swag-paypal-disputes-list';
import type { RouteLocationNormalized } from 'vue-router';

Shopware.Component.register('swag-paypal-disputes-detail', () => import('./page/swag-paypal-disputes-detail'));
Shopware.Component.register('swag-paypal-disputes-list', () => import('./page/swag-paypal-disputes-list'));

Shopware.Module.register('swag-paypal-disputes', {
    type: 'plugin',
    name: 'paypal-disputes',
    title: 'swag-paypal-disputes.general.mainMenuItemGeneral',
    description: 'swag-paypal-disputes.general.descriptionTextModule',
    version: '1.0.0',
    targetVersion: '1.0.0',
    color: '#F88962',
    icon: 'regular-comments',
    favicon: 'icon-module-customers.png',

    routes: {
        index: {
            component: 'swag-paypal-disputes-list',
            path: 'index',
            meta: {
                privilege: 'swag_paypal_disputes.viewer',
            },
        },

        detail: {
            component: 'swag-paypal-disputes-detail',
            path: 'detail/:disputeId/:salesChannelId?',
            props: {
                default(route: RouteLocationNormalized) {
                    return {
                        disputeId: route.params.disputeId,
                        salesChannelId: route.params.salesChannelId,
                    };
                },
            },
            meta: {
                privilege: 'swag_paypal_disputes.viewer',
                parentPath: 'swag.paypal.disputes.index',
            },
        },
    },

    navigation: [{
        id: 'swag-paypal-disputes',
        path: 'swag.paypal.disputes.index',
        label: 'swag-paypal-disputes.general.mainMenuItemGeneral',
        parent: 'sw-customer',
        privilege: 'swag_paypal_disputes.viewer',
    }],
});
