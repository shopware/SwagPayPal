<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1;

use Shopware\Core\Framework\Log\Package;

#[Package('checkout')]
final class PaymentIntentV1
{
    public const SALE = 'sale';
    public const AUTHORIZE = 'authorize';
    public const ORDER = 'order';

    public const INTENTS = [
        self::SALE,
        self::AUTHORIZE,
        self::ORDER,
    ];

    private function __construct()
    {
    }
}
