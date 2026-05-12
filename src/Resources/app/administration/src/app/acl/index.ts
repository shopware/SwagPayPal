Shopware.Service('privileges').addPrivilegeMappingEntry({
    category: 'permissions',
    parent: 'swag_paypal',
    key: 'swag_paypal',
    roles: {
        viewer: {
            privileges: [
                'sales_channel:read',
                'sales_channel_payment_method:read',
                'system_config:read',
            ],
            dependencies: [],
        },
        editor: {
            privileges: [
                'sales_channel:update',
                'sales_channel_payment_method:create',
                'sales_channel_payment_method:update',
                'system_config:update',
                'system_config:create',
                'system_config:delete',
            ],
            dependencies: [
                'swag_paypal.viewer',
            ],
        },
    },
});

Shopware.Service('privileges').addPrivilegeMappingEntry({
    category: 'permissions',
    parent: null,
    key: 'sales_channel',
    roles: {
        viewer: {
            privileges: [
                'swag_paypal_pos_sales_channel:read',
                'swag_paypal_pos_sales_channel_run:read',
                'swag_paypal_pos_sales_channel_run:update',
                'swag_paypal_pos_sales_channel_run:create',
                'swag_paypal_pos_sales_channel_run_log:read',
                'sales_channel_payment_method:read',
            ],
        },
        editor: {
            privileges: [
                'swag_paypal_pos_sales_channel:update',
                'swag_paypal_pos_sales_channel_run:delete',
                'payment_method:update',
            ],
        },
        creator: {
            privileges: [
                'swag_paypal_pos_sales_channel:create',
                'payment_method:create',
                'shipping_method:create',
                'delivery_time:create',
            ],
        },
        deleter: {
            privileges: [
                'swag_paypal_pos_sales_channel:delete',
            ],
        },
    },
});
