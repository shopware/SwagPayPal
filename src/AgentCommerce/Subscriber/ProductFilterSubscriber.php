<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce\Subscriber;

use Shopware\Core\Content\Product\Events\ProductGatewayCriteriaEvent;
use Shopware\Core\Framework\Api\Context\AdminSalesChannelApiSource;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\AgentCommerce\Routing\AgentSource;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
#[Package('checkout')]
class ProductFilterSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ProductGatewayCriteriaEvent::class => 'onProductGatewayCriteria',
        ];
    }

    public function onProductGatewayCriteria(ProductGatewayCriteriaEvent $event): void
    {
        $source = $event->getContext()->getSource();
        if (!$source instanceof AgentSource
            && (!$source instanceof AdminSalesChannelApiSource || !($source = $source->getOriginalContext()->getSource()) instanceof AgentSource)) {
            return;
        }

        $event->getCriteria()
            ->addFilter(new EqualsFilter('streams.id', $source->getStreamId()));
    }
}
