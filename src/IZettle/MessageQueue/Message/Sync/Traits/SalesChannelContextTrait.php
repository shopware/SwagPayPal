<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\MessageQueue\Message\Sync\Traits;

use Shopware\Core\System\SalesChannel\SalesChannelContext;

trait SalesChannelContextTrait
{
    /**
     * @var SalesChannelContext
     */
    private $salesChannelContext;

    public function setSalesChannelContext(SalesChannelContext $salesChannelContext): void
    {
        $this->salesChannelContext = $salesChannelContext;
    }

    public function getSalesChannelContext(): SalesChannelContext
    {
        return $this->salesChannelContext;
    }
}
