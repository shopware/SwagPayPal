<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Webhook\Handler;

use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\Pos\Api\Service\ApiKeyDecoder;
use Swag\PayPal\Pos\Api\Service\Converter\UuidConverter;
use Swag\PayPal\Pos\Api\Webhook\Payload\AbstractPayload;
use Swag\PayPal\Pos\Api\Webhook\Payload\InventoryBalanceChanged;
use Swag\PayPal\Pos\Api\Webhook\Payload\InventoryBalanceChanged\Balance;
use Swag\PayPal\Pos\Run\RunService;
use Swag\PayPal\Pos\Run\Task\InventoryTask;
use Swag\PayPal\Pos\Sync\Context\InventoryContextFactory;
use Swag\PayPal\Pos\Sync\Inventory\Calculator\LocalWebhookCalculator;
use Swag\PayPal\Pos\Sync\Inventory\LocalUpdater;
use Swag\PayPal\Pos\Sync\InventorySyncer;
use Swag\PayPal\Pos\Webhook\WebhookEventNames;

class InventoryChangedHandler extends AbstractWebhookHandler
{
    /**
     * @var RunService
     */
    private $runService;

    /**
     * @var LocalWebhookCalculator
     */
    private $localCalculator;

    /**
     * @var LocalUpdater
     */
    private $localUpdater;

    /**
     * @var InventorySyncer
     */
    private $inventorySyncer;

    /**
     * @var InventoryContextFactory
     */
    private $inventoryContextFactory;

    /**
     * @var EntityRepositoryInterface
     */
    private $productRepository;

    /**
     * @var UuidConverter
     */
    private $uuidConverter;

    public function __construct(
        ApiKeyDecoder $apiKeyDecoder,
        RunService $runService,
        LocalWebhookCalculator $localCalculator,
        LocalUpdater $localUpdater,
        InventorySyncer $inventorySyncer,
        InventoryContextFactory $inventoryContextFactory,
        EntityRepositoryInterface $productRepository,
        UuidConverter $uuidConverter
    ) {
        parent::__construct($apiKeyDecoder);
        $this->runService = $runService;
        $this->localCalculator = $localCalculator;
        $this->localUpdater = $localUpdater;
        $this->inventorySyncer = $inventorySyncer;
        $this->inventoryContextFactory = $inventoryContextFactory;
        $this->productRepository = $productRepository;
        $this->uuidConverter = $uuidConverter;
    }

    /**
     * {@inheritdoc}
     */
    public function getEventName(): string
    {
        return WebhookEventNames::INVENTORY_BALANCE_CHANGED;
    }

    /**
     * {@inheritdoc}
     */
    public function getPayloadClass(): string
    {
        return InventoryBalanceChanged::class;
    }

    /**
     * @param InventoryBalanceChanged $payload
     */
    public function execute(AbstractPayload $payload, SalesChannelEntity $salesChannel, Context $context): void
    {
        $inventoryContext = $this->inventoryContextFactory->getContext($salesChannel, $context);

        $productIds = [];

        foreach ($payload->getBalanceBefore() as $balanceBefore) {
            if ($balanceBefore->getLocationUuid() !== $inventoryContext->getStoreUuid()) {
                continue;
            }

            foreach ($payload->getBalanceAfter() as $balanceAfter) {
                if ($balanceAfter->getLocationUuid() !== $inventoryContext->getStoreUuid()) {
                    continue;
                }

                if ($balanceAfter->getVariantUuid() !== $balanceBefore->getVariantUuid()) {
                    continue;
                }

                $productId = $this->prepareProduct($balanceBefore, $balanceAfter);
                if ($productId !== null) {
                    $productIds[] = $productId;
                }
            }
        }

        $criteria = new Criteria($productIds);

        /** @var ProductCollection $productCollection */
        $productCollection = $this->productRepository->search($criteria, $context)->getEntities();

        $runId = $this->runService->startRun($salesChannel->getId(), InventoryTask::TASK_NAME_INVENTORY, $context);

        $inventoryContext->setProductIds($productIds);
        $this->inventoryContextFactory->updateLocal($inventoryContext);

        $changes = $this->localUpdater->updateLocal($productCollection, $inventoryContext);
        $this->inventorySyncer->updateLocalChanges($changes, $inventoryContext);

        $this->runService->writeLog($runId, $context);
        $this->runService->finishRun($runId, $context);
    }

    private function prepareProduct(Balance $balanceBefore, Balance $balanceAfter): ?string
    {
        $change = $balanceAfter->getBalance() - $balanceBefore->getBalance();

        if ($change === 0) {
            return null;
        }

        $productUuid = $this->uuidConverter->convertUuidToV4($balanceBefore->getProductUuid());
        $variantUuid = $this->uuidConverter->convertUuidToV4($balanceBefore->getVariantUuid());

        if ($this->uuidConverter->incrementUuid($productUuid) !== $variantUuid) {
            $productUuid = $variantUuid;
        }

        $this->localCalculator->addFixedUpdate($productUuid, $change);

        return $productUuid;
    }
}
