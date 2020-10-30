Shopware.Service('privileges').addPrivilegeMappingEntry({
    category: 'permissions',
    parent: 'swag_paypal',
    key: 'swag_paypal',
    roles: {
        viewer: {
            privileges: [
                'sales_channel:read',
                'sales_channel_payment_method:read',
                'system_config:read'
            ],
            dependencies: []
        },
        editor: {
            privileges: [
                'sales_channel:update',
                'sales_channel_payment_method:create',
                'sales_channel_payment_method:update',
                'system_config:update',
                'system_config:create',
                'system_config:delete'
            ],
            dependencies: [
                'swag_paypal.viewer'
            ]
        }
    }
});
