<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgenticCommerce\SalesChannel;

use Shopware\Core\Framework\Api\Context\AdminSalesChannelApiSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextServiceInterface;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextServiceParameters;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\PaymentMethod;

/**
 * @internal
 */
#[Package('checkout')]
abstract class AbstractAgenticCommerceRoute
{
    protected SalesChannelContextServiceInterface $contextService;

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

    protected function createPaymentMethod(string $token, ?string $payerId = null): PaymentMethod
    {
        $method = new PaymentMethod();
        $method->setToken($token);

        if ($payerId) {
            $method->setPayerId($payerId);
        }

        return $method;
    }
}
