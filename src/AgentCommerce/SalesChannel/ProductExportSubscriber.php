<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce\SalesChannel;

use Shopware\Core\Content\ProductExport\Event\ProductExportProductCriteriaEvent;
use Shopware\Core\Content\ProductExport\ProductExportEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelCollection;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
#[Package('checkout')]
class ProductExportSubscriber implements EventSubscriberInterface
{
    /**
     * @param EntityRepository<SalesChannelCollection> $salesChannelRepository
     */
    public function __construct(
        protected EntityRepository $salesChannelRepository,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ProductExportProductCriteriaEvent::class => 'productExportProductCriteria',
        ];
    }

    public function productExportProductCriteria(ProductExportProductCriteriaEvent $event): void
    {
        // TODO: use public AgentCommerceSalesChannelType const
        if ($event->getProductExport()->getSalesChannel()?->getTypeId() !== 'e3f8c9b2f1a44d4db0f793542e31d2c9') {
            return;
        }

        $this->addMediaAssociation($event->getCriteria());
        $this->loadSalesChannelCountry($event->getProductExport(), $event->getContext());
    }

    private function addMediaAssociation(Criteria $criteria): void
    {
        $criteria->getAssociation('media')
            ->addFilter(new EqualsFilter('position', 1))
            ->addAssociation('media');
    }

    private function loadSalesChannelCountry(ProductExportEntity $export, Context $context): void
    {
        $criteria = new Criteria([$export->getStorefrontSalesChannelId()]);
        $criteria->addAssociation('country');

        $salesChannel = $this->salesChannelRepository->search($criteria, $context)->first();
        if ($salesChannel) {
            $export->setStorefrontSalesChannel($salesChannel);
        }
    }
}
