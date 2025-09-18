<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Tests\AgentCommerce\Util;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
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

        $repository = $this->createMock(EntityRepository::class);
        $repository
            ->method('searchIds')
            ->willReturn($idResult);

        $transformer = new ShopwareCartTransformer(
            $repository,
            $repository,
            $repository
        );

        $customerData = $transformer->extractCustomerData((new PayPalCart())->assign(self::requestData()), $overallRandomId, Context::createDefaultContext());

        static::assertSame('John', $customerData['firstName']);
        static::assertSame('Smith', $customerData['lastName']);
        static::assertSame('john.smith@example.com', $customerData['email']);
        static::assertSame('john.smith@example.com', $customerData['email']);
        static::assertSame($overallRandomId, $customerData['salesChannelId']);
        static::assertSame($overallRandomId, $customerData['groupId']);
        static::assertSame($overallRandomId, $customerData['shippingAddress']['salutationId']);
        static::assertSame($overallRandomId, $customerData['shippingAddress']['countryId']);
        static::assertSame('John', $customerData['shippingAddress']['firstName']);
        static::assertSame('Smith', $customerData['shippingAddress']['lastName']);
        static::assertSame('12345', $customerData['shippingAddress']['zipcode']);
        static::assertSame('San Jose', $customerData['shippingAddress']['city']);
        static::assertSame('123 Main Street', $customerData['shippingAddress']['street']);
        static::assertSame('+1 12345-6789', $customerData['shippingAddress']['phoneNumber']);
        static::assertArrayHasKey('billingAddress', $customerData);
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
            $this->createMock(EntityRepository::class)
        );

        $transformer->extractCustomerData((new PayPalCart())->assign(self::requestData()), Uuid::randomHex(), Context::createDefaultContext());
    }

    private static function requestData(): array
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
