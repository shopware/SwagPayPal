<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PayPal;

/**
 * @deprecated tag:v2.0.0 - This class will be final in version 2.0.0
 */
/*final */class BaseURL
{
    public const SANDBOX = 'https://api.sandbox.paypal.com/v1/';
    public const LIVE = 'https://api.paypal.com/v1/';

    private function __construct()
    {
    }
}
