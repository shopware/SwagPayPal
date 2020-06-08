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
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelEntity;
use Swag\PayPal\IZettle\Run\Administration\LogCleaner;
use Swag\PayPal\IZettle\Run\RunService;
use Swag\PayPal\IZettle\Sync\ImageSyncer;
use Swag\PayPal\IZettle\Sync\InventorySyncer;
use Swag\PayPal\IZettle\Sync\ProductSelection;
use Swag\PayPal\IZettle\Sync\ProductSyncer;
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
     * @var ProductSyncer
     */
    private $productSyncer;

    /**
     * @var ImageSyncer
     */
    private $imageSyncer;

    /**
     * @var InventorySyncer
     */
    private $inventorySyncer;

    /**
     * @var EntityRepositoryInterface
     */
    private $salesChannelRepository;

    /**
     * @var RunService
     */
    private $runService;

    /**
     * @var LogCleaner
     */
    private $logCleaner;

    /**
     * @var ProductSelection
     */
    private $productSelection;

    public function __construct(
        ProductSyncer $productSyncer,
        ImageSyncer $imageSyncer,
        InventorySyncer $inventorySyncer,
        EntityRepositoryInterface $salesChannelRepository,
        RunService $runService,
        LogCleaner $logCleaner,
        ProductSelection $productSelection
    ) {
        $this->productSyncer = $productSyncer;
        $this->imageSyncer = $imageSyncer;
        $this->inventorySyncer = $inventorySyncer;
        $this->salesChannelRepository = $salesChannelRepository;
        $this->runService = $runService;
        $this->logCleaner = $logCleaner;
        $this->productSelection = $productSelection;
    }

    /**
     * @Route("/api/v{version}/paypal/izettle/sync/{salesChannelId}/products", name="api.paypal.izettle.sync.products", methods={"GET"})
     */
    public function syncProducts(string $salesChannelId, Context $context): Response
    {
        $salesChannel = $this->getSalesChannel($salesChannelId, $context);

        $run = $this->runService->startRun($salesChannelId, $context);
        $this->productSyncer->syncProducts($salesChannel, $context);
        $this->runService->finishRun($run, $context);

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    /**
     * @Route("/api/v{version}/paypal/izettle/sync/{salesChannelId}/images", name="api.paypal.izettle.sync.images", methods={"GET"})
     */
    public function syncImages(string $salesChannelId, Context $context): Response
    {
        $salesChannel = $this->getSalesChannel($salesChannelId, $context);

        /** @var IZettleSalesChannelEntity $iZettleSalesChannel */
        $iZettleSalesChannel = $salesChannel->getExtension(SwagPayPal::SALES_CHANNEL_IZETTLE_EXTENSION);

        $run = $this->runService->startRun($salesChannelId, $context);
        $this->imageSyncer->syncImages($iZettleSalesChannel, $context);
        $this->runService->finishRun($run, $context);

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    /**
     * @Route("/api/v{version}/paypal/izettle/sync/{salesChannelId}/inventory", name="api.paypal.izettle.sync.inventory", methods={"GET"})
     */
    public function syncInventory(string $salesChannelId, Context $context): Response
    {
        $salesChannel = $this->getSalesChannel($salesChannelId, $context);

        /** @var IZettleSalesChannelEntity $iZettleSalesChannel */
        $iZettleSalesChannel = $salesChannel->getExtension(SwagPayPal::SALES_CHANNEL_IZETTLE_EXTENSION);

        $run = $this->runService->startRun($salesChannelId, $context);
        $this->inventorySyncer->syncInventory($iZettleSalesChannel, $context);
        $this->runService->finishRun($run, $context);

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    /**
     * @Route("/api/v{version}/paypal/izettle/sync/{salesChannelId}", name="api.paypal.izettle.sync", methods={"GET"})
     */
    public function syncAll(string $salesChannelId, Context $context): Response
    {
        $salesChannel = $this->getSalesChannel($salesChannelId, $context);

        /** @var IZettleSalesChannelEntity $iZettleSalesChannel */
        $iZettleSalesChannel = $salesChannel->getExtension(SwagPayPal::SALES_CHANNEL_IZETTLE_EXTENSION);

        $run = $this->runService->startRun($salesChannelId, $context);
        $this->productSyncer->syncProducts($salesChannel, $context);
        $this->imageSyncer->syncImages($iZettleSalesChannel, $context);
        $this->productSyncer->syncProducts($salesChannel, $context);
        $this->inventorySyncer->syncInventory($iZettleSalesChannel, $context);
        $this->runService->finishRun($run, $context);

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    /**
     * @Route("/api/v{version}/paypal/izettle/log/cleanup/{salesChannelId}", name="api.paypal.izettle.log.cleanup", methods={"GET"})
     */
    public function cleanUpLog(string $salesChannelId, Context $context): Response
    {
        $salesChannel = $this->getSalesChannel($salesChannelId, $context);

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
        $salesChannel = $this->getSalesChannel($salesChannelId, $context);

        /** @var IZettleSalesChannelEntity $iZettleSalesChannel */
        $iZettleSalesChannel = $salesChannel->getExtension(SwagPayPal::SALES_CHANNEL_IZETTLE_EXTENSION);

        $productLogSearch = $this->productSelection->getProductLogCollection(
            $iZettleSalesChannel,
            $limit * ($page - 1),
            $limit,
            $context
        );

        return new JsonResponse($productLogSearch);
    }

    private function getSalesChannel(string $salesChannelId, Context $context): SalesChannelEntity
    {
        $criteria = new Criteria();
        $criteria->setIds([$salesChannelId]);
        $criteria->addFilter(new EqualsFilter('typeId', SwagPayPal::SALES_CHANNEL_TYPE_IZETTLE));
        $criteria->addFilter(new EqualsFilter('active', true));
        $criteria->addAssociation('currency');

        /** @var SalesChannelEntity|null $salesChannel */
        $salesChannel = $this->salesChannelRepository->search($criteria, $context)->first();

        if ($salesChannel === null) {
            throw new InvalidSalesChannelIdException($salesChannelId);
        }

        return $salesChannel;
    }
}
