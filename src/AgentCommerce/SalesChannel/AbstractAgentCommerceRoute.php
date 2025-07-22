<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce\SalesChannel;

use Shopware\Core\Framework\Api\Context\AdminSalesChannelApiSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextServiceParameters;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

/**
 * @internal
 */
#[Package('checkout')]
abstract class AbstractAgentCommerceRoute
{
    protected SalesChannelContextService $contextService;

    protected function createSalesChannelContext(string $token, string $salesChannelId, Context $context): SalesChannelContext
    {
        $source = $context->getSource();
        if ($source instanceof AdminSalesChannelApiSource) {
            $context = $source->getOriginalContext();
        }

        return $this->contextService->get(new SalesChannelContextServiceParameters(
            salesChannelId: $salesChannelId,
            token: $token,
            originalContext: $context
        ));
    }
}
