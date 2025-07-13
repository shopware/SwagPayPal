Shopware.Component.override(
    'sw-sales-channel-menu',
    Shopware.Component.extend(
        'swag-paypal-agent-commerce-sales-channel-menu',
        'sw-sales-channel-menu',
        import('./extension/sw-sales-channel-menu'),
    ),
);

Shopware.Component.override(
    'sw-sales-channel-modal',
    Shopware.Component.extend(
        'swag-paypal-agent-commerce-sales-channel-modal',
        'sw-sales-channel-modal',
        import('./extension/sw-sales-channel-modal'),
    ),
);

Shopware.Component.extend('swag-paypal-agent-commerce-sales-channel-detail', 'sw-sales-channel-detail', import('./extension/sw-sales-channel-detail'));

Shopware.Component.extend('swag-paypal-agent-commerce-sales-channel-detail-base', 'sw-sales-channel-detail-base', import('./extension/sw-sales-channel-detail-base'));

Shopware.Component.extend('swag-paypal-agent-commerce-sales-channel-create', 'sw-sales-channel-create', import('./extension/sw-sales-channel-create'));

Shopware.Component.extend('swag-paypal-agent-commerce-sales-channel-create-base', 'sw-sales-channel-create-base', import('./extension/sw-sales-channel-create-base'));

Shopware.Module.register('swag-paypal-agent-commerce', {
    type: 'core',
    name: 'swag-paypal-agent-commerce-sales-channel',
    title: 'Agent Commerce Sales Channel',
    description: 'The module for managing Sales Channels.',
    color: '#14D7A5',
    icon: 'regular-artificial-intelligence',
    entity: 'sales_channel',

    routes: {
        create: {
            component: 'swag-paypal-agent-commerce-sales-channel-create',
            path: 'create/:typeId',
            redirect: {
                name: 'swag.paypal.agent.commerce.create.base',
            },
            children: {
                base: {
                    component: 'swag-paypal-agent-commerce-sales-channel-create-base',
                    path: 'base',
                    meta: {
                        parentPath: 'swag.paypal.agent.commerce.create',
                        privilege: 'sales_channel.creator',
                    },
                },
            },
        },
        detail: {
            component: 'swag-paypal-agent-commerce-sales-channel-detail',
            path: 'detail/:id',
            meta: {
                parentPath: 'sw.sales.channel.list',
                privilege: 'sales_channel.viewer',
            },
            redirect: {
                name: 'swag.paypal.agent.commerce.detail.base',
            },
            children: {
                base: {
                    component: 'swag-paypal-agent-commerce-sales-channel-detail-base',
                    path: 'base',
                    meta: {
                        parentPath: 'sw.sales.channel.list',
                        privilege: 'sales_channel.viewer',
                    },
                },
            },
        },
    },
});
