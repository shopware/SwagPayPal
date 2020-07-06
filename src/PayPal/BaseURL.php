<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PayPal;

final class BaseURL
{
    public const SANDBOX = 'https://api.sandbox.paypal.com/';
    public const LIVE = 'https://api.paypal.com/';

    private function __construct()
    {
    }
}
