<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce\Validation;

use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Adapter\Translation\AbstractTranslator;
use Shopware\Core\Framework\Log\Package;
use Shopware\PayPalSDK\Builder\AgenticCommerce\V1\ValidationIssueBuilder;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\Context\InventoryIssueContext;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\ResolutionOption;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\ValidationIssue;

#[Package('checkout')]
class ValidationIssues
{
    /**
     * @internal
     */
    public function __construct(
        private readonly AbstractTranslator $translator,
    ) {
    }

    public function outOfStock(LineItem $item, ?ProductEntity $restockProduct): ValidationIssue
    {
        $builder = new ValidationIssueBuilder();

        $builder
            ->withCode(ValidationIssue::CODE__INVENTORY_ISSUE)
            ->withType(ValidationIssue::TYPE__BUSINESS_RULE)
            ->withMessage($this->translator->trans('swag_paypal.agent_commerce.validation_issue.out_of_stock.message'))
            ->withUserMessage($this->translator->trans('swag_paypal.agent_commerce.validation_issue.out_of_stock.user_message'))
            ->withItemId($item->getPayloadValue('productNumber')) // @phpstan-ignore method.deprecated
            ->addResolutionOption()
                ->withAction(ResolutionOption::ACTION__REMOVE_ITEM)
                ->withLabel('swag_paypal.agent_commerce.validation_issue.out_of_stock.resolution_option.remove.label')
                ->withMetadata()
                ->withCostImpact((string) (-1 * $item->getPrice()?->getTotalPrice()))
                ->end()
            ->end();

        $stock = $item->getPayloadValue('stock'); // @phpstan-ignore method.deprecated

        $inventoryContext = new InventoryIssueContext();
        $inventoryContext->setSpecificIssue($stock > 0 ? InventoryIssueContext::ISSUE__INSUFFICIENT_INVENTORY : InventoryIssueContext::ISSUE__ITEM_OUT_OF_STOCK);
        $inventoryContext->setAvailableQuantity($stock);
        $inventoryContext->setRequestedQuantity($item->getQuantity());

        $wait = $builder->addResolutionOption()
            ->withAction(ResolutionOption::ACTION__WAIT_FOR_RESTOCK)
            ->withLabel('swag_paypal.agent_commerce.validation_issue.out_of_stock.resolution_option.wait.label');

        if ($restockProduct) {
            $wait->withMetadata()->withEstimatedTime($restockProduct->getRestockTime() . ' Days');
            $inventoryContext->setRestockDate(\date('Y-m-d\T00:00:00', (int) strtotime('+' . $restockProduct->getRestockTime() . ' days')));
        }

        $builder->withContext($inventoryContext);

        return $builder->build();
    }
}
