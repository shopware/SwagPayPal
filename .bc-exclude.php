<?php declare(strict_types=1);

return [
    'filePatterns' => [
        '**/src/DevOps/**',
    ],
    'errors' => [
        // Storefront package is not installed
        \preg_quote('"Shopware\Storefront\Framework\Cookie\CookieProviderInterface" could not be found in the located source'),
    ],
];
