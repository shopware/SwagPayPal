<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Tests\AgenticCommerce\Util;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Swag\PayPal\AgenticCommerce\Exception\AgentException;
use Swag\PayPal\AgenticCommerce\Util\PayPalCartFactory;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(PayPalCartFactory::class)]
class PayPalCartFactoryTest extends TestCase
{
    #[DataProvider('dataProviderCartData')]
    public function testCreatePayPalCart(array $data, ?AgentException $expectedException = null): void
    {
        if ($expectedException) {
            $this->expectException($expectedException::class);
            $this->expectExceptionMessage($expectedException->getMessage());
        }

        $payPalCart = (new PayPalCartFactory())->create($data);

        static::assertTrue($payPalCart->isset('items'));
        static::assertGreaterThan(0, $payPalCart->getItems()->count());
    }

    public static function dataProviderCartData(): array
    {
        return [
            'no items' => [[], AgentException::requiredFieldsMissing('cart.items')],
            'empty items' => [['items' => []], AgentException::requiredFieldsMissing('cart.items')],
            'no variantId' => [['items' => [self::createCartItem(null, 1)]], AgentException::requiredFieldsMissing(\sprintf('cart.items.%s.variantId', 0))],
            'invalid variantId' => [['items' => [self::createCartItem('asdf', 1)]], AgentException::requiredFieldInvalid(\sprintf('cart.items.%s.variantId', 0), 'Not a valid UUID')],
            'no quantity' => [['items' => [self::createCartItem(Uuid::randomHex())]], AgentException::requiredFieldsMissing(\sprintf('cart.items.%s.quantity', 0))],
            'valid item' => [self::createItems()],
            'valid customer with item' => [array_merge(self::createItems(), self::createCustomer())],
            'empty customer' => [['customer' => ['random_property' => 'value']], AgentException::requiredFieldsMissing('cart.customer.emailAddress')],
            'no customer email' => [self::unsetProperty(self::createCustomer(), 'customer', 'email_address'), AgentException::requiredFieldsMissing('cart.customer.emailAddress')],
            'no customer name' => [self::unsetProperty(self::createCustomer(), 'customer', 'name'), AgentException::requiredFieldsMissing('cart.customer.name')],
            'no customer shipping address' => [self::unsetProperty(self::createCustomer(), 'shipping_address'), AgentException::requiredFieldsMissing('cart.shippingAddress')],
            'no customer shipping address address line' => [self::unsetProperty(self::createCustomer(), 'shipping_address', 'address_line_1'), AgentException::requiredFieldsMissing('address.addressLine1')],
            'no customer shipping address admin area' => [self::unsetProperty(self::createCustomer(), 'shipping_address', 'admin_area_2'), AgentException::requiredFieldsMissing('address.adminArea2')],
            'no customer shipping address country code' => [self::unsetProperty(self::createCustomer(), 'shipping_address', 'country_code'), AgentException::requiredFieldsMissing('address.countryCode')],
            'no customer billing address address line' => [self::unsetProperty(self::createCustomer(), 'billing_address', 'address_line_1'), AgentException::requiredFieldsMissing('address.addressLine1')],
            'no customer billing address admin area' => [self::unsetProperty(self::createCustomer(), 'billing_address', 'admin_area_2'), AgentException::requiredFieldsMissing('address.adminArea2')],
            'no customer billing address country code' => [self::unsetProperty(self::createCustomer(), 'billing_address', 'country_code'), AgentException::requiredFieldsMissing('address.countryCode')],
        ];
    }

    private static function createItems(): array
    {
        return ['items' => [self::createCartItem(Uuid::randomHex(), 1)]];
    }

    private static function createCartItem(?string $variantId = null, ?int $quantity = null): array
    {
        $item = [];
        if ($variantId !== null) {
            $item['variant_id'] = $variantId;
        }

        if ($quantity !== null) {
            $item['quantity'] = $quantity;
        }

        return $item;
    }

    private static function createCustomer(): array
    {
        return [
            'customer' => [
                'email_address' => 'email@example.com',
                'name' => ['given_name' => 'Mustermann', 'surname' => 'Max'],
            ],
            'shipping_address' => [
                'address_line_1' => '123 Main Street',
                'admin_area_2' => 'City',
                'country_code' => 'DE',
            ],
            'billing_address' => [
                'address_line_1' => '456 Other Street',
                'admin_area_2' => 'City 2',
                'country_code' => 'DE',
            ],
        ];
    }

    private static function unsetProperty(array $data, string $property, ?string $chained = null): array
    {
        if (!isset($data[$property])) {
            return $data;
        }

        if ($chained && isset($data[$property][$chained])) {
            unset($data[$property][$chained]);
        } else {
            unset($data[$property]);
        }

        return $data;
    }
}
