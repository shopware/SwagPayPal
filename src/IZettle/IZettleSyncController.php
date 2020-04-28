<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle;

use Shopware\Core\Framework\Api\Exception\InvalidSalesChannelIdException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\IZettle\Exception\UnexpectedSalesChannelTypeException;
use Swag\PayPal\IZettle\Sync\ProductSyncer;
use Swag\PayPal\SwagPayPal;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"api"})
 */
class IZettleSyncController extends AbstractController
{
    /**
     * @var ProductSyncer
     */
    private $productSyncer;

    /**
     * @var EntityRepositoryInterface
     */
    private $salesChannelRepository;

    public function __construct(
        ProductSyncer $productSyncer,
        EntityRepositoryInterface $salesChannelRepository
    ) {
        $this->productSyncer = $productSyncer;
        $this->salesChannelRepository = $salesChannelRepository;
    }

    /**
     * @Route("/api/v{version}/paypal/izettle/product-sync/{salesChannelId}", name="api.paypal.izettle.product_sync", methods={"GET"})
     */
    public function syncProducts(string $salesChannelId, Context $context): Response
    {
        $criteria = new Criteria();
        $criteria->setIds([$salesChannelId]);
        $criteria->addFilter(new EqualsFilter('typeId', SwagPayPal::SALES_CHANNEL_TYPE_IZETTLE));
        $criteria->addFilter(new EqualsFilter('active', true));
        $criteria->addAssociation('currency');

        /** @var SalesChannelEntity|null $salesChannel */
        $salesChannel = $this->salesChannelRepository->search($criteria, $context)->first();

        if ($salesChannel === null || !($salesChannel->getActive())) {
            throw new InvalidSalesChannelIdException($salesChannelId);
        }

        if ($salesChannel->getTypeId() !== SwagPayPal::SALES_CHANNEL_TYPE_IZETTLE) {
            throw new UnexpectedSalesChannelTypeException($salesChannel->getTypeId());
        }

        $this->productSyncer->syncProducts($salesChannel, $context);

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
