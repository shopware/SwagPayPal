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
    ],
];
