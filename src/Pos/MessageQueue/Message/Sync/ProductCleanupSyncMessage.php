<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\MessageQueue\Message\Sync;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Pos\MessageQueue\Message\AbstractSyncMessage;
use Swag\PayPal\Pos\MessageQueue\Message\Sync\Traits\SalesChannelContextAwareMessageInterface;
use Swag\PayPal\Pos\MessageQueue\Message\Sync\Traits\SalesChannelContextTrait;

#[Package('checkout')]
class ProductCleanupSyncMessage extends AbstractSyncMessage implements SalesChannelContextAwareMessageInterface
{
    use SalesChannelContextTrait;
}
