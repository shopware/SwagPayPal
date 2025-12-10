<?php declare(strict_types=1);

return [
    'filePatterns' => [
        '**/src/DevOps/**',
    ],
    'errors' => [
        // vendor false positive
        \preg_quote('An enum expression Monolog\Level::Debug is not supported in class Monolog\Handler\AbstractHandler'),
        // Storefront package is not installed
        \preg_quote('"Shopware\Storefront\Framework\Cookie\CookieProviderInterface" could not be found in the located source'),
        // Apple Pay it now available on third-party browsers
        \preg_quote('Value of constant Swag\PayPal\Checkout\SalesChannel\MethodEligibilityRoute::REMOVABLE_PAYMENT_HANDLERS changed from array ('),
        // internal const
        \preg_quote('Value of constant Swag\PayPal\Setting\Settings::DEFAULT_VALUES changed from array') . '.*',
    ],
];
