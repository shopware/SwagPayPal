<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgenticCommerce\Subscriber;

use Shopware\Core\Checkout\Cart\Order\CartConvertedEvent;
use Shopware\Core\Framework\Api\Context\AdminSalesChannelApiSource;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\AgenticCommerce\Routing\AgentSource;
use Swag\PayPal\SwagPayPal;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
#[Package('checkout')]
class OrderFlagSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            CartConvertedEvent::class => 'onCartConverted',
        ];
    }

    public function onCartConverted(CartConvertedEvent $event): void
    {
        $source = $event->getContext()->getSource();
        if (!$source instanceof AgentSource
            && (!$source instanceof AdminSalesChannelApiSource || !($source = $source->getOriginalContext()->getSource()) instanceof AgentSource)) {
            return;
        }

        // The order itself belongs to the storefront sales channel, so keep track of the agentic one
        $convertedCart = $event->getConvertedCart();
        $convertedCart['customFields'][SwagPayPal::ORDER_CUSTOM_FIELDS_PAYPAL_AGENTIC_COMMERCE_SALES_CHANNEL_ID] = $source->salesChannelId;

        $event->setConvertedCart($convertedCart);
    }
}
