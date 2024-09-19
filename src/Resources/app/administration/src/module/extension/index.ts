Shopware.Component.override('sw-first-run-wizard-paypal-credentials', () => import('./sw-first-run-wizard/sw-first-run-wizard-paypal-credentials'));

Shopware.Component.override('sw-sales-channel-modal-detail', () => import('./sw-sales-channel-modal-detail'));

Shopware.Component.override('sw-sales-channel-modal-grid', () => import('./sw-sales-channel-modal-grid'));

Shopware.Component.register('swag-paypal-overview-card', () => import('./sw-settings-payment/components/swag-paypal-overview-card'));
Shopware.Component.override('sw-settings-payment-detail', () => import('./sw-settings-payment/sw-settings-payment-detail'));
Shopware.Component.override('sw-settings-payment-list', () => import('./sw-settings-payment/sw-settings-payment-list'));

Shopware.Component.override('sw-settings-shipping-detail', () => import('./sw-settings-shipping/sw-settings-shipping-detail'));

Shopware.Component.override('sw-extension-card-base', () => import('./sw-extension-card-base'));

export {};
