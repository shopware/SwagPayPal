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
        // PayPal does not always return a vetting status
        \preg_quote('Type of property Swag\PayPal\RestApi\V1\Api\MerchantIntegrations\Product#$vettingStatus changed from string to'),
        \preg_quote('The return type of Swag\PayPal\RestApi\V1\Api\MerchantIntegrations\Product#getVettingStatus() changed from string to'),
    ],
];
