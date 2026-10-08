<?php declare(strict_types=1);

return [
    'filePatterns' => [
        '**/src/DevOps/**',
    ],
    'errors' => [
        // expected const changes
        \preg_quote('Value of constant Swag\PayPal\SecurityAdvisories::ADVISORIES changed from array') . '.*',
        // vendor false positive
        \preg_quote('An enum expression Monolog\Level::Debug is not supported in class Monolog\Handler\AbstractHandler'),
        // Storefront package is not installed
        \preg_quote('"Shopware\Storefront\Framework\Cookie\CookieProviderInterface" could not be found in the located source'),
    ],
];
