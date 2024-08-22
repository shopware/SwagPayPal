<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Webhook;

use Shopware\Core\Framework\Log\Package;

/**
 * @url https://github.com/iZettle/api-documentation/blob/master/pusher.adoc
 */
#[Package('checkout')]
final class WebhookEventNames
{
    public const INVENTORY_BALANCE_CHANGED = 'InventoryBalanceChanged';

    public const TEST_MESSAGE = 'TestMessage';

    private function __construct()
    {
    }
}
