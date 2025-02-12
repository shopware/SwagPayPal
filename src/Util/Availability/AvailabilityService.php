<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util\Availability;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;

#[Package('checkout')]
class AvailabilityService
{
    private PaymentMethodDataRegistry $paymentMethodDataRegistry;

    /**
     * @internal
     */
    public function __construct(PaymentMethodDataRegistry $paymentMethodDataRegistry)
    {
        $this->paymentMethodDataRegistry = $paymentMethodDataRegistry;
    }

    /**
     * @return string[]
     */
    public function filterPaymentMethods(PaymentMethodCollection $paymentMethods, Cart $cart, SalesChannelContext $salesChannelContext): array
    {
        $handlers = [];

        $context = AvailabilityContextBuilder::buildFromCart($cart, $salesChannelContext);

        foreach ($paymentMethods as $paymentMethod) {
            if (!$this->isAvailable($paymentMethod, $context)) {
                $handlers[] = $paymentMethod->getHandlerIdentifier();
            }
        }

        return $handlers;
    }

    /**
     * @return string[]
     */
    public function filterPaymentMethodsByOrder(PaymentMethodCollection $paymentMethods, Cart $cart, OrderEntity $order, SalesChannelContext $salesChannelContext): array
    {
        $handlers = [];

        $context = AvailabilityContextBuilder::buildFromOrder($order, $salesChannelContext);

        foreach ($paymentMethods as $paymentMethod) {
            if (!$this->isAvailable($paymentMethod, $context)) {
                $handlers[] = $paymentMethod->getHandlerIdentifier();
            }
        }

        return $handlers;
    }

    public function isPaymentMethodAvailable(PaymentMethodEntity $paymentMethod, Cart $cart, SalesChannelContext $salesChannelContext): bool
    {
        return $this->isAvailable(
            $paymentMethod,
            AvailabilityContextBuilder::buildFromCart($cart, $salesChannelContext)
        );
    }

    private function isAvailable(PaymentMethodEntity $paymentMethod, AvailabilityContext $context): bool
    {
        $methodData = $this->paymentMethodDataRegistry->getPaymentMethodByHandler($paymentMethod->getHandlerIdentifier());
        if ($methodData === null) {
            return true;
        }

        return $methodData->isAvailable($context);
    }
}
