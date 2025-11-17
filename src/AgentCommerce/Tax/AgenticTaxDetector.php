<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce\Tax;

use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\Tax\AbstractTaxDetector;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @internal
 */
#[Package('checkout')]
class AgenticTaxDetector extends AbstractTaxDetector
{
    public function __construct(
        private readonly AbstractTaxDetector $decorated,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function getDecorated(): AbstractTaxDetector
    {
        return $this->decorated;
    }

    public function useGross(SalesChannelContext $context): bool
    {
        $routeScope = $this->requestStack->getMainRequest()?->attributes->get('_routeScope');
        if (\is_array($routeScope) && \in_array('paypal-agent', $routeScope, true)) {
            return false;
        }

        return $this->getDecorated()->useGross($context);
    }

    public function isNetDelivery(SalesChannelContext $context): bool
    {
        return $this->getDecorated()->isNetDelivery($context);
    }

    public function getTaxState(SalesChannelContext $context): string
    {
        $routeScope = $this->requestStack->getMainRequest()?->attributes->get('_routeScope');
        if (!\is_array($routeScope) || !\in_array('paypal-agent', $routeScope, true)) {
            return $this->getDecorated()->getTaxState($context);
        }

        if ($this->isNetDelivery($context)) {
            return CartPrice::TAX_STATE_FREE;
        }

        if ($this->useGross($context)) {
            return CartPrice::TAX_STATE_GROSS;
        }

        return CartPrice::TAX_STATE_NET;
    }

    public function isCompanyTaxFree(SalesChannelContext $context, CountryEntity $shippingLocationCountry): bool
    {
        return $this->getDecorated()->isCompanyTaxFree($context, $shippingLocationCountry);
    }
}
