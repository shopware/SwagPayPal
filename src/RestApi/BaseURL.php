<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi;

use Shopware\Core\Framework\Log\Package;

#[Package('checkout')]
final class BaseURL
{
    public const SANDBOX = 'https://api-m.sandbox.paypal.com/';
    public const LIVE = 'https://api-m.paypal.com/';

    private function __construct()
    {
    }
}
