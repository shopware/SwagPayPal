<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Disputes\Item;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiCollection;

/**
 * @extends PayPalApiCollection<DisputedTransaction>
 */
#[Package('checkout')]
class DisputedTransactionCollection extends PayPalApiCollection
{
    public static function getExpectedClass(): string
    {
        return DisputedTransaction::class;
    }
}
