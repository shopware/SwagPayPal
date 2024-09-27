<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Api\Exception\InvalidSalesChannelIdException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\Pos\Run\Administration\LogCleaner;
use Swag\PayPal\Pos\Run\Administration\SyncResetter;
use Swag\PayPal\Pos\Run\RunService;
use Swag\PayPal\Pos\Run\Task\CompleteTask;
use Swag\PayPal\Pos\Run\Task\ImageTask;
use Swag\PayPal\Pos\Run\Task\InventoryTask;
use Swag\PayPal\Pos\Run\Task\ProductTask;
use Swag\PayPal\Pos\Sync\ProductSelection;
use Swag\PayPal\SwagPayPal;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Package('checkout')]
#[Route(defaults: ['_routeScope' => ['api']])]
class PosSyncController extends AbstractController
{
    /**
     * @internal
     */
    public function __construct(
        private readonly EntityRepository $salesChannelRepository,
        private readonly CompleteTask $completeTask,
        private readonly ProductTask $productTask,
        private readonly ImageTask $imageTask,
        private readonly InventoryTask $inventoryTask,
        private readonly LogCleaner $logCleaner,
        private readonly RunService $runService,
        private readonly SyncResetter $syncResetter,
        private readonly ProductSelection $productSelection,
    ) {
    }

    #[OA\Post(
        path: '/api/_action/paypal/pos/sync/{salesChannelId}/products',
        operationId: 'posSyncProducts',
        tags: ['Admin Api', 'PayPal'],
        parameters: [
            new OA\Parameter(
                parameter: 'salesChannelId',
                name: 'salesChannelId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', pattern: '^[0-9a-f]{32}$')
            ),
        ],
        responses: [new OA\Response(
            response: Response::HTTP_OK,
            description: 'Run ID of the started sync process',
            content: new OA\JsonContent(properties: [new OA\Property(
                property: 'runId',
                type: 'string',
            )])
        )]
    )]
    #[Route(path: '/api/_action/paypal/pos/sync/{salesChannelId}/products', name: 'api.action.paypal.pos.sync.products', methods: ['POST'], defaults: ['_acl' => ['sales_channel.viewer']])]
    public function syncProducts(string $salesChannelId, Context $context): Response
    {
        $salesChannel = $this->getSalesChannel($salesChannelId, $context);

        $runId = $this->productTask->execute($salesChannel, $context);

        return new JsonResponse(['runId' => $runId]);
    }

    #[OA\Post(
        path: '/api/_action/paypal/pos/sync/{salesChannelId}/images',
        operationId: 'posSyncImages',
        tags: ['Admin Api', 'PayPal'],
        parameters: [
            new OA\Parameter(
                parameter: 'salesChannelId',
                name: 'salesChannelId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', pattern: '^[0-9a-f]{32}$')
            ),
        ],
        responses: [new OA\Response(
            response: Response::HTTP_OK,
            description: 'Run ID of the started sync process',
            content: new OA\JsonContent(properties: [new OA\Property(
                property: 'runId',
                type: 'string',
            )])
        )]
    )]
    #[Route(path: '/api/_action/paypal/pos/sync/{salesChannelId}/images', name: 'api.action.paypal.pos.sync.images', methods: ['POST'], defaults: ['_acl' => ['sales_channel.viewer']])]
    public function syncImages(string $salesChannelId, Context $context): Response
    {
        $salesChannel = $this->getSalesChannel($salesChannelId, $context);

        $runId = $this->imageTask->execute($salesChannel, $context);

        return new JsonResponse(['runId' => $runId]);
    }

    #[OA\Post(
        path: '/api/_action/paypal/pos/sync/{salesChannelId}/inventory',
        operationId: 'posSyncInventory',
        tags: ['Admin Api', 'PayPal'],
        parameters: [
            new OA\Parameter(
                parameter: 'salesChannelId',
                name: 'salesChannelId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', pattern: '^[0-9a-f]{32}$')
            ),
        ],
        responses: [new OA\Response(
            response: Response::HTTP_OK,
            description: 'Run ID of the started sync process',
            content: new OA\JsonContent(properties: [new OA\Property(
                property: 'runId',
                type: 'string',
            )])
        )]
    )]
    #[Route(path: '/api/_action/paypal/pos/sync/{salesChannelId}/inventory', name: 'api.action.paypal.pos.sync.inventory', methods: ['POST'], defaults: ['_acl' => ['sales_channel.viewer']])]
    public function syncInventory(string $salesChannelId, Context $context): Response
    {
        $salesChannel = $this->getSalesChannel($salesChannelId, $context);

        $runId = $this->inventoryTask->execute($salesChannel, $context);

        return new JsonResponse(['runId' => $runId]);
    }

    #[OA\Post(
        path: '/api/_action/paypal/pos/sync/{salesChannelId}',
        operationId: 'posSync',
        tags: ['Admin Api', 'PayPal'],
        parameters: [
            new OA\Parameter(
                parameter: 'salesChannelId',
                name: 'salesChannelId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', pattern: '^[0-9a-f]{32}$')
            ),
        ],
        responses: [new OA\Response(
            response: Response::HTTP_OK,
            description: 'Run ID of the started sync process',
            content: new OA\JsonContent(properties: [new OA\Property(
                property: 'runId',
                type: 'string',
            )])
        )]
    )]
    #[Route(path: '/api/_action/paypal/pos/sync/{salesChannelId}', name: 'api.action.paypal.pos.sync', methods: ['POST'], defaults: ['_acl' => ['sales_channel.viewer']])]
    public function syncAll(string $salesChannelId, Context $context): Response
    {
        $salesChannel = $this->getSalesChannel($salesChannelId, $context);

        $runId = $this->completeTask->execute($salesChannel, $context);

        return new JsonResponse(['runId' => $runId]);
    }

    #[OA\Post(
        path: '/api/_action/paypal/pos/sync/abort/{runId}',
        operationId: 'posSyncAbort',
        tags: ['Admin Api', 'PayPal'],
        parameters: [
            new OA\Parameter(
                parameter: 'runId',
                name: 'runId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [new OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Abortion was successful')]
    )]
    #[Route(path: '/api/_action/paypal/pos/sync/abort/{runId}', name: 'api.action.paypal.pos.sync.abort', methods: ['POST'], defaults: ['_acl' => ['sales_channel.viewer']])]
    public function abortSync(string $runId, Context $context): Response
    {
        $this->runService->abortRun($runId, $context);

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    #[OA\Post(
        path: '/api/_action/paypal/pos/sync/reset/{salesChannelId}',
        operationId: 'posSyncReset',
        tags: ['Admin Api', 'PayPal'],
        parameters: [
            new OA\Parameter(
                parameter: 'salesChannelId',
                name: 'salesChannelId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', pattern: '^[0-9a-f]{32}$')
            ),
        ],
        responses: [new OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Reset was successful')]
    )]
    #[Route(path: '/api/_action/paypal/pos/sync/reset/{salesChannelId}', name: 'api.action.paypal.pos.sync.reset', methods: ['POST'], defaults: ['_acl' => ['sales_channel.editor']])]
    public function resetSync(string $salesChannelId, Context $context): Response
    {
        $salesChannel = $this->getSalesChannel($salesChannelId, $context, true);

        $this->syncResetter->resetSync($salesChannel, $context);

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    #[OA\Post(
        path: '/api/_action/paypal/pos/log/cleanup/{salesChannelId}',
        operationId: 'posSyncCleanup',
        tags: ['Admin Api', 'PayPal'],
        parameters: [
            new OA\Parameter(
                parameter: 'salesChannelId',
                name: 'salesChannelId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', pattern: '^[0-9a-f]{32}$')
            ),
        ],
        responses: [new OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Cleanup was successful')]
    )]
    #[Route(path: '/api/_action/paypal/pos/log/cleanup/{salesChannelId}', name: 'api.action.paypal.pos.log.cleanup', methods: ['POST'], defaults: ['_acl' => ['sales_channel.editor']])]
    public function cleanUpLog(string $salesChannelId, Context $context): Response
    {
        $salesChannel = $this->getSalesChannel($salesChannelId, $context, true);

        $this->logCleaner->clearLog($salesChannel->getId(), $context);

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    #[OA\Get(
        path: '/api/paypal/pos/product-log/{salesChannelId}',
        operationId: 'posProductLog',
        tags: ['Admin Api', 'PayPal'],
        parameters: [
            new OA\Parameter(
                parameter: 'salesChannelId',
                name: 'salesChannelId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', pattern: '^[0-9a-f]{32}$')
            ),
            new OA\Parameter(
                parameter: 'limit',
                name: 'limit',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 10, minimum: 1)
            ),
            new OA\Parameter(
                parameter: 'page',
                name: 'page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 1, minimum: 1)
            ),
        ],
        responses: [new OA\Response(response: Response::HTTP_OK, description: 'Product log of the sales channel')]
    )]
    #[Route(path: '/api/paypal/pos/product-log/{salesChannelId}', name: 'api.paypal.pos.product-log', methods: ['GET'], defaults: ['_acl' => ['sales_channel.viewer']])]
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
        $criteria->addFilter(new EqualsFilter('typeId', SwagPayPal::SALES_CHANNEL_TYPE_POS));
        if (!$returnDisabled) {
            $criteria->addFilter(new EqualsFilter('active', true));
        }
        $criteria->addAssociation(SwagPayPal::SALES_CHANNEL_POS_EXTENSION);
        $criteria->addAssociation('currency');

        /** @var SalesChannelEntity|null $salesChannel */
        $salesChannel = $this->salesChannelRepository->search($criteria, $context)->first();

        if ($salesChannel === null) {
            throw new InvalidSalesChannelIdException($salesChannelId);
        }

        return $salesChannel;
    }
}
