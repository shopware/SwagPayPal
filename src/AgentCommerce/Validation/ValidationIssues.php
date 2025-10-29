<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce\Validation;

use Shopware\Core\Checkout\Cart\Address\Error\AddressValidationError;
use Shopware\Core\Checkout\Cart\Address\Error\BillingAddressBlockedError;
use Shopware\Core\Checkout\Cart\Address\Error\ShippingAddressBlockedError;
use Shopware\Core\Checkout\Cart\Error\Error;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\CashRounding;
use Shopware\Core\Content\Product\Cart\MinOrderQuantityError;
use Shopware\Core\Content\Product\Cart\ProductNotFoundError;
use Shopware\Core\Content\Product\Cart\ProductOutOfStockError;
use Shopware\Core\Content\Product\Cart\ProductStockReachedError;
use Shopware\Core\Content\Product\Cart\PurchaseStepsError;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Adapter\Translation\AbstractTranslator;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\CashRoundingConfig;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\Builder\ValidationIssueBuilder;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\Context\InventoryIssueContext;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\Context\PricingErrorContext;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\Referral\MetaData;
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

    public function outOfStock(LineItem $item, ?ProductEntity $restockProduct, CurrencyEntity $currency): ValidationIssue
    {
        $stock = $item->getPayloadValue('stock'); // @phpstan-ignore method.deprecated

        $builder = new ValidationIssueBuilder();
        $builder
            ->withCode(ValidationIssue::CODE__INVENTORY_ISSUE)
            ->withType(ValidationIssue::TYPE__BUSINESS_RULE)
            ->withMessage('Product stock insufficient')
            ->withUserMessage($this->translator->trans('paypal.agentCommerce.validationIssue.userMessage.outOfStock', ['%name%' => $item->getLabel(), '%count%' => $stock]))
            ->withItemId($item->getReferencedId() ?? '')
            ->addResolutionOption()
                ->withAction(ResolutionOption::ACTION__REMOVE_ITEM)
                ->withLabel($this->translator->trans('paypal.agentCommerce.validationIssue.resolutionOption.removeLabel'))
                ->withMetadata()
                ->withCostImpact('-' . $currency->getSymbol() . $item->getPrice()?->getTotalPrice())
                ->withPriority(MetaData::PRIORITY__LOW)
                ->end()
            ->end();

        $inventoryContext = new InventoryIssueContext();
        $inventoryContext->setSpecificIssue($stock > 0 ? InventoryIssueContext::ISSUE__INSUFFICIENT_INVENTORY : InventoryIssueContext::ISSUE__ITEM_OUT_OF_STOCK);
        $inventoryContext->setAvailableQuantity($stock);
        $inventoryContext->setRequestedQuantity($item->getQuantity());

        $wait = $builder->addResolutionOption()
            ->withAction(ResolutionOption::ACTION__WAIT_FOR_RESTOCK)
            ->withLabel($this->translator->trans('paypal.agentCommerce.validationIssue.resolutionOption.waitRestockLabel', ['%name%' => $item->getLabel()]));

        if ($restockProduct) {
            $wait->withMetadata()
                ->withEstimatedTime($this->translator->trans('paypal.agentCommerce.validationIssue.resolutionOption.estimatedTime', ['%days%' => $restockProduct->getRestockTime()]))
                ->withPriority(MetaData::PRIORITY__MEDIUM);
            $inventoryContext->setRestockDate(\date('Y-m-d\T00:00:00', (int) strtotime('+' . $restockProduct->getRestockTime() . ' days')));
        }

        $builder->withContext($inventoryContext);

        return $builder->build();
    }

    /**
     * @param numeric-string $initPrice
     */
    public function changedPrice(LineItem $lineItem, string $initPrice, CurrencyEntity $currency, CashRoundingConfig $roundingConfig): ValidationIssue
    {
        $unitPrice = (string) $lineItem->getPrice()?->getUnitPrice();
        $priceDiff = (new CashRounding())->cashRound((float) $unitPrice - (float) $initPrice, $roundingConfig);

        if ($priceDiff <= 0) {
            throw new \RuntimeException('Init price need to be lower then actual price');
        }

        $context = new PricingErrorContext();
        $context->setOriginalPrice($initPrice);
        $context->setCurrentPrice($unitPrice);
        $context->setCurrencyCode($currency->getIsoCode());
        $context->setPriceChangeReason(PricingErrorContext::PRICE_CHANGE_REASON__COMPONENT_COST_INCREASE);
        $context->setPriceIncrease((string) $priceDiff);

        $builder = new ValidationIssueBuilder();
        $builder
            ->withCode(ValidationIssue::CODE__PRICING_ERROR)
            ->withType(ValidationIssue::TYPE__BUSINESS_RULE)
            ->withMessage('Product price has changed')
            ->withUserMessage($this->translator->trans('paypal.agentCommerce.validationIssue.userMessage.priceChanged', ['%name%' => $lineItem->getLabel(), '%oldPrice%' => $initPrice, '%newPrice%' => $unitPrice]))
            ->withItemId($lineItem->getReferencedId() ?? '')
            ->withContext($context);

        $builder->addResolutionOption()
            ->withAction(ResolutionOption::ACTION__ACCEPT_NEW_PRICE)
            ->withLabel($this->translator->trans('paypal.agentCommerce.validationIssue.resolutionOption.acceptLabel', ['%option%' => $unitPrice]))
            ->withMetadata()
                ->withCostImpact('+' . $currency->getSymbol() . $priceDiff)
                ->withPriority(MetaData::PRIORITY__HIGH);

        $builder->addResolutionOption()
            ->withAction(ResolutionOption::ACTION__REMOVE_ITEM)
            ->withLabel($this->translator->trans('paypal.agentCommerce.validationIssue.resolutionOption.removeLabel'))
            ->withMetadata()
                ->withCostImpact('-' . $currency->getSymbol() . $initPrice)
                ->withPriority(MetaData::PRIORITY__MEDIUM);

        return $builder->build();
    }

    public function cartError(Error $error, string $localeCode): ValidationIssue
    {
        $validationIssue = new ValidationIssue();
        $validationIssue->setMessage($error->getId());
        $validationIssue->setMessage($error->getMessage());
        $validationIssue->setType(ValidationIssue::TYPE__BUSINESS_RULE);
        $validationIssue->setCode(ValidationIssue::CODE__BUSINESS_RULE_ERROR);

        /** @deprecated tag:v11.0.0 - "getTranslatedMessage" is added with v6.7.3.0 */
        // @phpstan-ignore function.alreadyNarrowedType
        if (\method_exists($error, 'getTranslatedMessage')) {
            $validationIssue->setUserMessage($error->getTranslatedMessage());
        } else {
            $parameters = [];
            foreach ($error->getParameters() as $key => $value) {
                $parameters['%' . $key . '%'] = $value;
            }

            $message = $this->translator->trans(
                'checkout.' . $error->getMessageKey(),
                $parameters,
                null,
                $localeCode
            );

            $validationIssue->setUserMessage($message);
        }

        switch ($error::class) {
            case ProductNotFoundError::class:
            case PurchaseStepsError::class:
            case MinOrderQuantityError::class:
            case ProductOutOfStockError::class:
            case ProductStockReachedError::class:
                $validationIssue->setCode(ValidationIssue::CODE__INVENTORY_ISSUE);
                $validationIssue->setItemId(str_replace($error->getMessageKey(), '', $error->getId()));

                break;
            case ShippingAddressBlockedError::class:
            case BillingAddressBlockedError::class:
            case AddressValidationError::class:
                $validationIssue->setCode(ValidationIssue::CODE__SHIPPING_ERROR);
        }

        return $validationIssue;
    }
}
