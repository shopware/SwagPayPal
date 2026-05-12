<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Webhook\Handler;

use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
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

#[Package('checkout')]
class InventoryChangedHandler extends AbstractWebhookHandler
{
    /**
     * @internal
     */
    public function __construct(
        private readonly ApiKeyDecoder $apiKeyDecoder,
        private readonly RunService $runService,
        private readonly LocalWebhookCalculator $localCalculator,
        private readonly LocalUpdater $localUpdater,
        private readonly InventorySyncer $inventorySyncer,
        private readonly InventoryContextFactory $inventoryContextFactory,
        private readonly EntityRepository $productRepository,
        private readonly UuidConverter $uuidConverter,
        private readonly bool $stockManagementEnabled,
    ) {
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
        if (!$this->stockManagementEnabled) {
            return;
        }

        if ($this->isOwnClientId($payload->getUpdated()->getClientUuid(), $salesChannel)) {
            return;
        }

        $inventoryContext = $this->inventoryContextFactory->getContext($salesChannel);

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

                foreach ($this->prepareProduct($balanceBefore, $balanceAfter) as $productId) {
                    $productIds[] = $productId;
                }
            }
        }

        $criteria = new Criteria($productIds);

        /** @var ProductCollection $productCollection */
        $productCollection = $this->productRepository->search($criteria, $context)->getEntities();

        $runId = $this->runService->startRun($salesChannel->getId(), InventoryTask::TASK_NAME_INVENTORY, [], $context);

        $inventoryContext->setProductIds($productCollection->getIds());
        $this->inventoryContextFactory->updateLocal($inventoryContext);

        $changes = $this->localUpdater->updateLocal($productCollection, $inventoryContext);
        $this->inventorySyncer->updateLocalChanges($changes, $inventoryContext);

        $this->runService->writeLog($runId, $context);
        $this->runService->finishRun($runId, $context);
    }

    /**
     * @return string[]
     */
    private function prepareProduct(Balance $balanceBefore, Balance $balanceAfter): array
    {
        $change = $balanceAfter->getBalance() - $balanceBefore->getBalance();

        if ($change === 0) {
            return [];
        }

        $productUuidV4 = $this->uuidConverter->convertUuidToV4($balanceBefore->getProductUuid());
        $productUuidV7 = $this->uuidConverter->convertUuidToV7($balanceBefore->getProductUuid());
        $variantUuidV4 = $this->uuidConverter->convertUuidToV4($balanceBefore->getVariantUuid());
        $variantUuidV7 = $this->uuidConverter->convertUuidToV7($balanceBefore->getVariantUuid());

        if ($this->uuidConverter->incrementUuid($productUuidV4) !== $variantUuidV4) {
            $productUuidV4 = $variantUuidV4;
        }
        if ($this->uuidConverter->incrementUuid($productUuidV7) !== $variantUuidV7) {
            $productUuidV7 = $variantUuidV7;
        }

        $this->localCalculator->addFixedUpdate($productUuidV4, $change);
        $this->localCalculator->addFixedUpdate($productUuidV7, $change);

        return [$productUuidV4, $productUuidV7];
    }

    private function isOwnClientId(?string $reportedClientId, SalesChannelEntity $salesChannel): bool
    {
        if ($reportedClientId === null) {
            return false;
        }

        $apiKey = $this->getPosSalesChannel($salesChannel)->getApiKey();

        $ownClientId = $this->apiKeyDecoder->decode($apiKey)->getPayload()->getClientId();

        return $reportedClientId === $ownClientId;
    }
}
