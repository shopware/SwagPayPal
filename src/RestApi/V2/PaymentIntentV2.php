<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2;

use Shopware\Core\Framework\Log\Package;

#[Package('checkout')]
final class PaymentIntentV2
{
    public const CAPTURE = 'CAPTURE';
    public const AUTHORIZE = 'AUTHORIZE';

    public const INTENTS = [
        self::CAPTURE,
        self::AUTHORIZE,
    ];

    private function __construct()
    {
    }
}
