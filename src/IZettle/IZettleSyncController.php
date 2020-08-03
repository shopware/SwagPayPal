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
use Swag\PayPal\IZettle\Run\Administration\LogCleaner;
use Swag\PayPal\IZettle\Run\Task\CompleteTask;
use Swag\PayPal\IZettle\Run\Task\ImageTask;
use Swag\PayPal\IZettle\Run\Task\InventoryTask;
use Swag\PayPal\IZettle\Run\Task\ProductTask;
use Swag\PayPal\IZettle\Sync\ProductSelection;
use Swag\PayPal\SwagPayPal;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"api"})
 */
class IZettleSyncController extends AbstractController
{
    /**
     * @var EntityRepositoryInterface
     */
    private $salesChannelRepository;

    /**
     * @var CompleteTask
     */
    private $completeTask;

    /**
     * @var ProductTask
     */
    private $productTask;

    /**
     * @var ImageTask
     */
    private $imageTask;

    /**
     * @var InventoryTask
     */
    private $inventoryTask;

    /**
     * @var LogCleaner
     */
    private $logCleaner;

    /**
     * @var ProductSelection
     */
    private $productSelection;

    public function __construct(
        EntityRepositoryInterface $salesChannelRepository,
        CompleteTask $completeTask,
        ProductTask $productTask,
        ImageTask $imageTask,
        InventoryTask $inventoryTask,
        LogCleaner $logCleaner,
        ProductSelection $productSelection
    ) {
        $this->salesChannelRepository = $salesChannelRepository;
        $this->completeTask = $completeTask;
        $this->productTask = $productTask;
        $this->imageTask = $imageTask;
        $this->inventoryTask = $inventoryTask;
        $this->logCleaner = $logCleaner;
        $this->productSelection = $productSelection;
    }

    /**
     * @Route("/api/v{version}/paypal/izettle/sync/{salesChannelId}/products", name="api.paypal.izettle.sync.products", methods={"GET"})
     */
    public function syncProducts(string $salesChannelId, Context $context): Response
    {
        $salesChannel = $this->getSalesChannel($salesChannelId, $context);

        $runId = $this->productTask->execute($salesChannel, $context);

        return new JsonResponse(['runId' => $runId]);
    }

    /**
     * @Route("/api/v{version}/paypal/izettle/sync/{salesChannelId}/images", name="api.paypal.izettle.sync.images", methods={"GET"})
     */
    public function syncImages(string $salesChannelId, Context $context): Response
    {
        $salesChannel = $this->getSalesChannel($salesChannelId, $context);

        $runId = $this->imageTask->execute($salesChannel, $context);

        return new JsonResponse(['runId' => $runId]);
    }

    /**
     * @Route("/api/v{version}/paypal/izettle/sync/{salesChannelId}/inventory", name="api.paypal.izettle.sync.inventory", methods={"GET"})
     */
    public function syncInventory(string $salesChannelId, Context $context): Response
    {
        $salesChannel = $this->getSalesChannel($salesChannelId, $context);

        $runId = $this->inventoryTask->execute($salesChannel, $context);

        return new JsonResponse(['runId' => $runId]);
    }

    /**
     * @Route("/api/v{version}/paypal/izettle/sync/{salesChannelId}", name="api.paypal.izettle.sync", methods={"GET"})
     */
    public function syncAll(string $salesChannelId, Context $context): Response
    {
        $salesChannel = $this->getSalesChannel($salesChannelId, $context);

        $runId = $this->completeTask->execute($salesChannel, $context);

        return new JsonResponse(['runId' => $runId]);
    }

    /**
     * @Route("/api/v{version}/paypal/izettle/log/cleanup/{salesChannelId}", name="api.paypal.izettle.log.cleanup", methods={"GET"})
     */
    public function cleanUpLog(string $salesChannelId, Context $context): Response
    {
        $salesChannel = $this->getSalesChannel($salesChannelId, $context, true);

        $this->logCleaner->cleanUpLog($salesChannel->getId(), $context);

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    /**
     * @Route("/api/v{version}/paypal/izettle/product-log/{salesChannelId}", name="api.paypal.izettle.product-log", methods={"GET"})
     */
    public function getProductLog(string $salesChannelId, Request $request, Context $context): Response
    {
        $limit = $request->query->getInt('limit', 10);
        $page = $request->query->getInt('page', 1);
        $salesChannel = $this->getSalesChannel($salesChannelId, $context, true);

        $productLogSearch = $this->productSelection->getProductLogCollection(
            $salesChannel,
            $limit * ($page - 1),
            $limit,
            $context
        );

        return new JsonResponse($productLogSearch);
    }

    private function getSalesChannel(string $salesChannelId, Context $context, bool $returnDisabled = false): SalesChannelEntity
    {
        $criteria = new Criteria([$salesChannelId]);
        $criteria->addFilter(new EqualsFilter('typeId', SwagPayPal::SALES_CHANNEL_TYPE_IZETTLE));
        if (!$returnDisabled) {
            $criteria->addFilter(new EqualsFilter('active', true));
        }
        $criteria->addAssociation('currency');

        /** @var SalesChannelEntity|null $salesChannel */
        $salesChannel = $this->salesChannelRepository->search($criteria, $context)->first();

        if ($salesChannel === null) {
            throw new InvalidSalesChannelIdException($salesChannelId);
        }

        return $salesChannel;
    }
}
