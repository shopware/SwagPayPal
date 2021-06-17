Shopware.Service('privileges').addPrivilegeMappingEntry({
    category: 'permissions',
    parent: 'swag_paypal',
    key: 'swag_paypal_disputes',
    roles: {
        viewer: {
            privileges: [
                'sales_channel:read',
            ],
            dependencies: [],
        },
    },
});
