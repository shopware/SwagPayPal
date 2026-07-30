<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Tests\AgenticCommerce\Validation;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Address\Error\AddressValidationError;
use Shopware\Core\Checkout\Cart\Address\Error\BillingAddressBlockedError;
use Shopware\Core\Checkout\Cart\Address\Error\BillingAddressCountryRegionMissingError;
use Shopware\Core\Checkout\Cart\Address\Error\BillingAddressSalutationMissingError;
use Shopware\Core\Checkout\Cart\Address\Error\ShippingAddressBlockedError;
use Shopware\Core\Checkout\Cart\Address\Error\ShippingAddressCountryRegionMissingError;
use Shopware\Core\Checkout\Cart\Address\Error\ShippingAddressSalutationMissingError;
use Shopware\Core\Checkout\Cart\Error\Error;
use Shopware\Core\Checkout\Cart\Error\GenericCartError;
use Shopware\Core\Checkout\Cart\Error\IncompleteLineItemError;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Gateway\Error\CheckoutGatewayError;
use Shopware\Core\Checkout\Payment\Cart\Error\PaymentMethodBlockedError;
use Shopware\Core\Checkout\Promotion\Cart\Error\AutoPromotionNotFoundError;
use Shopware\Core\Checkout\Promotion\Cart\Error\PromotionExcludedError;
use Shopware\Core\Checkout\Promotion\Cart\Error\PromotionNotEligibleError;
use Shopware\Core\Checkout\Promotion\Cart\Error\PromotionNotFoundError;
use Shopware\Core\Checkout\Promotion\Cart\Error\PromotionsOnCartPriceZeroError;
use Shopware\Core\Checkout\Promotion\Cart\PromotionCartAddedInformationError;
use Shopware\Core\Checkout\Promotion\Cart\PromotionCartDeletedInformationError;
use Shopware\Core\Checkout\Shipping\Cart\Error\ShippingMethodBlockedError;
use Shopware\Core\Content\Product\Cart\MinOrderQuantityError;
use Shopware\Core\Content\Product\Cart\ProductNotFoundError;
use Shopware\Core\Content\Product\Cart\ProductOutOfStockError;
use Shopware\Core\Content\Product\Cart\ProductStockReachedError;
use Shopware\Core\Content\Product\Cart\PurchaseStepsError;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Adapter\Translation\AbstractTranslator;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\CashRoundingConfig;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\Context\InventoryIssueContext;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\Context\PricingErrorContext;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\Referral\MetaData;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\ResolutionOption;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\ValidationIssue;
use Shopware\Storefront\Checkout\Cart\Error\PaymentMethodChangedError;
use Shopware\Storefront\Checkout\Cart\Error\ShippingMethodChangedError;
use Swag\PayPal\AgenticCommerce\Validation\ValidationIssues;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(ValidationIssues::class)]
class ValidationIssuesTest extends TestCase
{
    #[DataProvider('dataProviderOutOfStock')]
    public function testInsufficientInventory(int $stock, bool $isRestock): void
    {
        $translator = $this->createMock(AbstractTranslator::class);
        $translator
            ->method('trans')
            ->willReturnArgument(0);

        $validation = new ValidationIssues($translator);

        $product = null;
        if ($isRestock) {
            $product = new ProductEntity();
            $product->setRestockTime(10);
        }

        $currency = new CurrencyEntity();
        $currency->setSymbol('€');

        $uuid = Uuid::randomHex();
        $lineItem = new LineItem($uuid, 'product', $uuid, 10);
        $lineItem->setPrice(new CalculatedPrice(100, 1000, new CalculatedTaxCollection(), new TaxRuleCollection()));
        $lineItem->setPayloadValue('stock', $stock);

        $validationIssue = $validation->outOfStock($lineItem, $product, $currency);

        static::assertSame(ValidationIssue::CODE__INVENTORY_ISSUE, $validationIssue->getCode());
        static::assertSame(ValidationIssue::TYPE__BUSINESS_RULE, $validationIssue->getType());
        static::assertSame($uuid, $validationIssue->getItemId());

        static::assertSame('Product stock insufficient', $validationIssue->getMessage());
        static::assertStringContainsString('validationIssue.userMessage.outOfStock', $validationIssue->getUserMessage() ?? '');

        $context = $validationIssue->getContext();
        static::assertInstanceOf(InventoryIssueContext::class, $context);
        static::assertSame(max($stock, 0), $context->getAvailableQuantity());
        static::assertSame(10, $context->getRequestedQuantity());

        if ($stock > 0) {
            static::assertSame(InventoryIssueContext::ISSUE__INSUFFICIENT_INVENTORY, $context->getSpecificIssue());
        } else {
            static::assertSame(InventoryIssueContext::ISSUE__ITEM_OUT_OF_STOCK, $context->getSpecificIssue());
        }

        $options = $validationIssue->getResolutionOptions();
        $remove = $options->first();

        static::assertInstanceOf(ResolutionOption::class, $remove);
        static::assertSame(ResolutionOption::ACTION__REMOVE_ITEM, $remove->getAction());
        static::assertStringContainsString('validationIssue.resolutionOption.removeLabel', $remove->getLabel());
        static::assertSame('-€1000', $remove->getMetadata()->getCostImpact());
        static::assertSame(MetaData::PRIORITY__LOW, $remove->getMetadata()->getPriority());

        if ($isRestock) {
            static::assertSame(\date('Y-m-d\T00:00:00', (int) strtotime('+10 days')), $context->getRestockDate());

            $wait = $options->get(1);

            static::assertInstanceOf(ResolutionOption::class, $wait);
            static::assertSame(ResolutionOption::ACTION__WAIT_FOR_RESTOCK, $wait->getAction());
            static::assertStringContainsString('validationIssue.resolutionOption.waitRestockLabel', $wait->getLabel());
            static::assertStringContainsString('validationIssue.resolutionOption.estimatedTime', $wait->getMetadata()->getEstimatedTime());
            static::assertSame(MetaData::PRIORITY__MEDIUM, $wait->getMetadata()->getPriority());
        }
    }

    public static function dataProviderOutOfStock(): array
    {
        return [
            'Insufficient stock with restock' => [5, true],
            'Insufficient stock without restock' => [5, false],
            'Out of stock with restock' => [0, true],
            'Out of stock without restock' => [-1, false],
        ];
    }

    /**
     * @param numeric-string $initPrice
     */
    #[DataProvider('dataProviderChangedPrice')]
    public function testChangedPrice(string $initPrice, bool $exception): void
    {
        if ($exception) {
            // TODO: create real exception
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessageMatches('/\A' . \preg_quote('Init price need to be lower then actual price', '/') . '\z/');
        }

        $translator = $this->createMock(AbstractTranslator::class);
        $translator
            ->method('trans')
            ->willReturnArgument(0);

        $validation = new ValidationIssues($translator);

        $currency = new CurrencyEntity();
        $currency->setSymbol('€');
        $currency->setIsoCode('EUR');

        $price = 100;
        $diff = (string) ($price - (float) $initPrice);
        $uuid = Uuid::randomHex();
        $lineItem = new LineItem($uuid, 'product', $uuid, 10);
        $lineItem->setPrice(new CalculatedPrice($price, $price * 10, new CalculatedTaxCollection(), new TaxRuleCollection()));

        $validationIssue = $validation->changedPrice($lineItem, $initPrice, $currency, new CashRoundingConfig(2, 2, false));

        static::assertSame(ValidationIssue::CODE__PRICING_ERROR, $validationIssue->getCode());
        static::assertSame(ValidationIssue::TYPE__BUSINESS_RULE, $validationIssue->getType());
        static::assertSame($uuid, $validationIssue->getItemId());

        static::assertSame('Product price has changed', $validationIssue->getMessage());
        static::assertStringContainsString('validationIssue.userMessage.priceChanged', $validationIssue->getUserMessage() ?? '');

        $context = $validationIssue->getContext();
        static::assertInstanceOf(PricingErrorContext::class, $context);
        static::assertSame($initPrice, $context->getOriginalPrice());
        static::assertSame((string) $price, $context->getCurrentPrice());
        static::assertSame($diff, $context->getPriceIncrease());
        static::assertSame('EUR', $context->getCurrencyCode());
        static::assertSame('component_cost_increase', $context->getPriceChangeReason());

        $accept = $validationIssue->getResolutionOptions()->first();
        $remove = $validationIssue->getResolutionOptions()->get(1);

        static::assertInstanceOf(ResolutionOption::class, $accept);
        static::assertInstanceOf(ResolutionOption::class, $remove);

        static::assertSame(ResolutionOption::ACTION__ACCEPT_NEW_PRICE, $accept->getAction());
        static::assertSame('+€' . $diff, $accept->getMetadata()->getCostImpact());
        static::assertSame(MetaData::PRIORITY__HIGH, $accept->getMetadata()->getPriority());
        static::assertStringContainsString('validationIssue.resolutionOption.acceptLabel', $accept->getLabel());

        static::assertSame(ResolutionOption::ACTION__REMOVE_ITEM, $remove->getAction());
        static::assertSame('-€' . $initPrice, $remove->getMetadata()->getCostImpact());
        static::assertSame(MetaData::PRIORITY__MEDIUM, $remove->getMetadata()->getPriority());
        static::assertStringContainsString('validationIssue.resolutionOption.removeLabel', $remove->getLabel());
    }

    /**
     * @return array<string, array{numeric-string, bool}>
     */
    public static function dataProviderChangedPrice(): array
    {
        return [
            'equal price' => ['100', true],
            'lower price' => ['110', true],
            'greater price' => ['80', false],
        ];
    }

    #[DataProvider('dataProviderCartError')]
    public function testCartError(Error $error, string $code): void
    {
        $translator = $this->createMock(AbstractTranslator::class);
        $translator
            ->method('trans')
            ->willReturnArgument(0);

        $validation = new ValidationIssues($translator);

        $validationIssue = $validation->cartError($error, 'en-GB');

        static::assertSame($code, $validationIssue->getCode());
        static::assertSame(ValidationIssue::TYPE__BUSINESS_RULE, $validationIssue->getType());
    }

    /**
     * @return iterable<class-string<Error>, array{0: Error}>
     */
    public static function dataProviderCartError(): iterable
    {
        $code = ValidationIssue::CODE__BUSINESS_RULE_ERROR;

        yield AddressValidationError::class => [new AddressValidationError(true, new ConstraintViolationList()), ValidationIssue::CODE__SHIPPING_ERROR];
        yield BillingAddressBlockedError::class => [new BillingAddressBlockedError('foo'), ValidationIssue::CODE__SHIPPING_ERROR];
        yield BillingAddressCountryRegionMissingError::class => [new BillingAddressCountryRegionMissingError(self::createCustomerAddress()), $code];
        yield BillingAddressSalutationMissingError::class => [new BillingAddressSalutationMissingError(self::createCustomerAddress()), $code];
        yield ShippingAddressBlockedError::class => [new ShippingAddressBlockedError('foo'), ValidationIssue::CODE__SHIPPING_ERROR];
        yield ShippingAddressCountryRegionMissingError::class => [new ShippingAddressCountryRegionMissingError(self::createCustomerAddress()), $code];
        yield ShippingAddressSalutationMissingError::class => [new ShippingAddressSalutationMissingError(self::createCustomerAddress()), $code];
        yield GenericCartError::class => [new GenericCartError('foo', 'bar', [], Error::LEVEL_ERROR, false, false, false), $code];
        yield IncompleteLineItemError::class => [new IncompleteLineItemError('foo', 'bar'), $code];
        yield CheckoutGatewayError::class => [new CheckoutGatewayError('foo', Error::LEVEL_NOTICE, true), $code];
        yield PaymentMethodBlockedError::class => [new PaymentMethodBlockedError('foo', 'reason', Uuid::randomHex()), $code];
        yield AutoPromotionNotFoundError::class => [new AutoPromotionNotFoundError('foo'), $code];
        yield PromotionExcludedError::class => [new PromotionExcludedError('foo'), $code];
        yield PromotionNotEligibleError::class => [new PromotionNotEligibleError('foo'), $code];
        yield PromotionNotFoundError::class => [new PromotionNotFoundError('foo'), $code];
        yield PromotionsOnCartPriceZeroError::class => [new PromotionsOnCartPriceZeroError(['foo', 'bar']), $code];
        yield PromotionCartAddedInformationError::class => [new PromotionCartAddedInformationError(self::createLineItem()), $code];
        yield PromotionCartDeletedInformationError::class => [new PromotionCartDeletedInformationError(self::createLineItem()), $code];
        yield ShippingMethodBlockedError::class => [new ShippingMethodBlockedError('foo', Uuid::randomHex(), 'reason'), $code];
        yield MinOrderQuantityError::class => [new MinOrderQuantityError(Uuid::randomHex(), 'foo', 5), ValidationIssue::CODE__INVENTORY_ISSUE];
        yield ProductNotFoundError::class => [new ProductNotFoundError(Uuid::randomHex()), ValidationIssue::CODE__INVENTORY_ISSUE];
        yield ProductOutOfStockError::class => [new ProductOutOfStockError(Uuid::randomHex(), 'foo'), ValidationIssue::CODE__INVENTORY_ISSUE];
        yield ProductStockReachedError::class => [new ProductStockReachedError(Uuid::randomHex(), 'foo', 1), ValidationIssue::CODE__INVENTORY_ISSUE];
        yield PurchaseStepsError::class => [new PurchaseStepsError(Uuid::randomHex(), 'foo', 5), ValidationIssue::CODE__INVENTORY_ISSUE];
        yield PaymentMethodChangedError::class => [new PaymentMethodChangedError('foo', 'bar', Uuid::randomHex(), Uuid::randomHex(), 'reason'), $code];
        yield ShippingMethodChangedError::class => [new ShippingMethodChangedError('foo', 'bar', Uuid::randomHex(), Uuid::randomHex(), 'reason'), $code];
    }

    private static function createCustomerAddress(): CustomerAddressEntity
    {
        $address = new CustomerAddressEntity();
        $address->setId(Uuid::randomHex());

        $address->setCustomerId(Uuid::randomHex());
        $address->setCountryId(Uuid::randomHex());
        $address->setFirstName('John');
        $address->setLastName('Doe');
        $address->setZipcode('12345');
        $address->setCity('Testcity');
        $address->setStreet('Teststreet 1');

        return $address;
    }

    private static function createLineItem(): LineItem
    {
        $lineItem = new LineItem(Uuid::randomHex(), LineItem::PRODUCT_LINE_ITEM_TYPE, Uuid::randomHex(), 2);
        $lineItem->setLabel('LineItem label');

        return $lineItem;
    }
}
