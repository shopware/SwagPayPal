<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\MessageQueue\Message\Sync\Traits;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

#[Package('checkout')]
trait SalesChannelContextTrait
{
    protected SalesChannelContext $salesChannelContext;

    protected string $contextToken;

    public function setSalesChannelContext(SalesChannelContext $salesChannelContext): void
    {
        $this->salesChannelContext = $salesChannelContext;
        $this->contextToken = $this->salesChannelContext->getToken();
    }

    public function getSalesChannelContext(): SalesChannelContext
    {
        return $this->salesChannelContext;
    }

    public function getContextToken(): string
    {
        return $this->contextToken;
    }

    public function setContextToken(string $contextToken): void
    {
        $this->contextToken = $contextToken;
    }

    public function getContext(): Context
    {
        if (!$this->isHydrated()) {
            return parent::getContext();
        }

        return $this->salesChannelContext->getContext();
    }
}
