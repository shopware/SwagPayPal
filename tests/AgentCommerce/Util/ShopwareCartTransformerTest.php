<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Tests\AgentCommerce\Util;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\LineItemFactoryHandler\ProductLineItemFactory;
use Shopware\Core\Checkout\Promotion\Cart\PromotionItemBuilder;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Country\Aggregate\CountryState\CountryStateCollection;
use Shopware\Core\System\Country\Aggregate\CountryState\CountryStateEntity;
use Shopware\Core\System\Country\CountryCollection;
use Shopware\Core\System\Country\CountryDefinition;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\Coupon;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\PayPalCart;
use Swag\PayPal\AgentCommerce\Exception\AgentException;
use Swag\PayPal\AgentCommerce\Util\ShopwareCartTransformer;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(ShopwareCartTransformer::class)]
class ShopwareCartTransformerTest extends TestCase
{
    public function testExtractCustomerData(): void
    {
        $overallRandomId = Uuid::randomHex();
        $idResult = new IdSearchResult(1, [['primaryKey' => $overallRandomId]], new Criteria(), Context::createDefaultContext());

        $state1 = new CountryStateEntity();
        $state1->setId(Uuid::randomHex());
        $state1->setShortCode('CACA');

        $caId = Uuid::randomHex();
        $state2 = new CountryStateEntity();
        $state2->setId($caId);
        $state2->setShortCode('CA');

        $nyId = Uuid::randomHex();
        $state3 = new CountryStateEntity();
        $state3->setId($nyId);
        $state3->setShortCode('US-NY');

        $country = new CountryEntity();
        $country->setId($overallRandomId);
        $country->setStates(new CountryStateCollection([$state1, $state2, $state3]));

        $result = new EntitySearchResult(CountryDefinition::ENTITY_NAME, 1, new CountryCollection([$country]), null, new Criteria(), Context::createDefaultContext());

        $repository = $this->createMock(EntityRepository::class);
        $repository
            ->method('searchIds')
            ->willReturn($idResult);
        $repository
            ->method('search')
            ->willReturn($result);

        $transformer = new ShopwareCartTransformer(
            $repository,
            $repository,
            $repository,
            $this->createMock(ProductLineItemFactory::class),
            $this->createMock(PromotionItemBuilder::class),
        );

        $customerData = $transformer->extractCustomerData((new PayPalCart())->assign(self::requestCustomerData()), $overallRandomId, Context::createDefaultContext());

        static::assertSame('John', $customerData['firstName']);
        static::assertSame('Smith', $customerData['lastName']);
        static::assertSame('john.smith@example.com', $customerData['email']);
        static::assertSame('john.smith@example.com', $customerData['email']);
        static::assertSame($overallRandomId, $customerData['salesChannelId']);
        static::assertSame($overallRandomId, $customerData['groupId']);
        static::assertSame($overallRandomId, $customerData['shippingAddress']['salutationId']);
        static::assertSame($overallRandomId, $customerData['shippingAddress']['countryId']);
        static::assertSame($caId, $customerData['shippingAddress']['countryStateId']);
        static::assertArrayHasKey('billingAddress', $customerData);
        static::assertSame($overallRandomId, $customerData['billingAddress']['salutationId']);
        static::assertSame($overallRandomId, $customerData['billingAddress']['countryId']);
        static::assertSame($nyId, $customerData['billingAddress']['countryStateId']);

        static::assertSame('John', $customerData['shippingAddress']['firstName']);
        static::assertSame('Smith', $customerData['shippingAddress']['lastName']);
        static::assertSame('12345', $customerData['shippingAddress']['zipcode']);
        static::assertSame('San Jose', $customerData['shippingAddress']['city']);
        static::assertSame('123 Main Street', $customerData['shippingAddress']['street']);
        static::assertSame('+1 12345-6789', $customerData['shippingAddress']['phoneNumber']);

        static::assertSame('John', $customerData['billingAddress']['firstName']);
        static::assertSame('Smith', $customerData['billingAddress']['lastName']);
        static::assertSame('10001', $customerData['billingAddress']['zipcode']);
        static::assertSame('New York', $customerData['billingAddress']['city']);
        static::assertSame('456 Payment Boulevard', $customerData['billingAddress']['street']);
        static::assertSame('+1 12345-6789', $customerData['billingAddress']['phoneNumber']);
        static::assertTrue($customerData['guest']);
    }

    public function testExtractCustomerDataNoCountryCodeFound(): void
    {
        $exception = AgentException::requiredFieldInvalid('address.countryCode', 'Country not found');

        $this->expectException($exception::class);
        $this->expectExceptionMessage($exception->getMessage());

        $transformer = new ShopwareCartTransformer(
            $this->createMock(EntityRepository::class),
            $this->createMock(EntityRepository::class),
            $this->createMock(EntityRepository::class),
            $this->createMock(ProductLineItemFactory::class),
            $this->createMock(PromotionItemBuilder::class),
        );

        $transformer->extractCustomerData((new PayPalCart())->assign(self::requestCustomerData()), Uuid::randomHex(), Context::createDefaultContext());
    }

    public function testGetLineItems(): void
    {
        $itemId = Uuid::randomHex();
        $itemFactory = $this->createMock(ProductLineItemFactory::class);
        $itemFactory
            ->method('create')
            ->with(['id' => $itemId, 'quantity' => 2])
            ->willReturn(new LineItem($itemId, LineItem::PRODUCT_LINE_ITEM_TYPE, $itemId, 2));

        $transformer = new ShopwareCartTransformer(
            $this->createMock(EntityRepository::class),
            $this->createMock(EntityRepository::class),
            $this->createMock(EntityRepository::class),
            $itemFactory,
            new PromotionItemBuilder(),
        );

        $data = [
            'items' => [
                ['variant_id' => $itemId, 'quantity' => 2],
            ],
            'coupons' => [
                ['action' => Coupon::APPLY, 'code' => 'some-code'],
            ],
        ];

        $lineItems = $transformer->getLineItems((new PayPalCart())->assign($data), $this->createMock(SalesChannelContext::class));
        static::assertCount(2, $lineItems);

        static::assertSame($itemId, $lineItems[0]->getId());
        static::assertSame(2, $lineItems[0]->getQuantity());
        static::assertSame(LineItem::PRODUCT_LINE_ITEM_TYPE, $lineItems[0]->getType());

        $key = PromotionItemBuilder::PLACEHOLDER_PREFIX . 'some-code';
        static::assertSame(Uuid::fromStringToHex($key), $lineItems[1]->getId());
        static::assertSame('some-code', $lineItems[1]->getReferencedId());
        static::assertSame(1, $lineItems[1]->getQuantity());
        static::assertSame(LineItem::PROMOTION_LINE_ITEM_TYPE, $lineItems[1]->getType());
    }

    private static function requestCustomerData(): array
    {
        return [
            'customer' => [
                'name' => [
                    'given_name' => 'John',
                    'surname' => 'Smith',
                ],
                'email_address' => 'john.smith@example.com',
                'phone' => [
                    'country_code' => '1',
                    'national_number' => '12345',
                    'extension_number' => '6789',
                ],
            ],
            'shipping_address' => [
                'address_line_1' => '123 Main Street',
                'address_line_2' => 'Apt 4B',
                'admin_area_2' => 'San Jose',
                'admin_area_1' => 'CA',
                'postal_code' => '12345',
                'country_code' => 'US',
            ],
            'billing_address' => [
                'address_line_1' => '456 Payment Boulevard',
                'address_line_2' => 'Suite 789',
                'admin_area_2' => 'New York',
                'admin_area_1' => 'NY',
                'postal_code' => '10001',
                'country_code' => 'US',
            ],
        ];
    }
}
