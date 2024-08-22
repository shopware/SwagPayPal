<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\MessageQueue\Message\Sync\Traits;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

#[Package('checkout')]
interface SalesChannelContextAwareMessageInterface
{
    public function setSalesChannelContext(SalesChannelContext $salesChannelContext): void;

    public function getSalesChannelContext(): SalesChannelContext;

    public function getContextToken(): string;

    public function getSalesChannelId(): string;
}
