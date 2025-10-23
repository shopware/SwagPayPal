<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\AgentCommerce\SalesChannel;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Shipping\ShippingMethodEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\Exception\ConstraintViolationException;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\ContextTokenResponse;
use Shopware\Core\System\SalesChannel\SalesChannel\ContextSwitchRoute;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\PayPalCart;
use Swag\PayPal\AgentCommerce\Exception\AgentException;
use Swag\PayPal\AgentCommerce\SalesChannel\CreateCartRoute;
use Swag\PayPal\AgentCommerce\SalesChannel\Response\AgentCartResponse;
use Swag\PayPal\AgentCommerce\SalesChannel\UpdateCartRoute;
use Swag\PayPal\AgentCommerce\Util\ShopwareCartTransformer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * @internal
 */
class UpdateCartRouteTest extends TestCase
{
    private UpdateCartRoute $updateCartRoute;

    private MockObject&SalesChannelContextService $contextService;

    private MockObject&ShopwareCartTransformer $shopwareCartTransformer;

    private MockObject&CreateCartRoute $createCartRoute;

    private MockObject&EntityRepository $customerRepository;

    private MockObject&EntityRepository $customerAddressRepository;

    private MockObject&CartService $cartService;

    private MockObject&ContextSwitchRoute $contextSwitchRoute;

    private MockObject&SalesChannelContext $salesChannelContext;

    protected function setUp(): void
    {
        $this->contextService = $this->createMock(SalesChannelContextService::class);
        $this->shopwareCartTransformer = $this->createMock(ShopwareCartTransformer::class);
        $this->createCartRoute = $this->createMock(CreateCartRoute::class);
        $this->customerRepository = $this->createMock(EntityRepository::class);
        $this->customerAddressRepository = $this->createMock(EntityRepository::class);
        $this->cartService = $this->createMock(CartService::class);
        $this->contextSwitchRoute = $this->createMock(ContextSwitchRoute::class);
        $this->salesChannelContext = $this->createMock(SalesChannelContext::class);

        $this->updateCartRoute = new UpdateCartRoute(
            $this->contextService,
            $this->shopwareCartTransformer,
            $this->createCartRoute,
            $this->customerRepository,
            $this->customerAddressRepository,
            $this->cartService,
            $this->contextSwitchRoute
        );
    }

    public function testInvalidCartToken(): void
    {
        $this->expectException(AgentException::class);
        $this->expectExceptionMessage('Cart ID format is invalid. Expected format: CART-[a-zA-Z0-9]{32}');

        $this->updateCartRoute->updateCart('invalid-token', new Request(), $this->salesChannelContext);
    }

    public function testDeleteCustomer(): void
    {
        $content = json_encode(self::createItems());
        static::assertIsString($content);

        $customer = new CustomerEntity();
        $customer->setId(Uuid::randomHex());

        $this->salesChannelContext
            ->method('getCustomer')
            ->willReturn($customer);

        $this->customerRepository
            ->expects($this->once())
            ->method('delete')
            ->with([['id' => $customer->getId()]]);

        $this->cartService
            ->expects($this->once())
            ->method('deleteCart');

        $payPalCart = new PayPalCart();
        $payPalCart->setValidationStatus(PayPalCart::VALIDATION_STATUS__VALID);
        $createResponse = new AgentCartResponse($payPalCart);

        $this->createCartRoute
            ->expects($this->once())
            ->method('createCart')
            ->willReturn($createResponse);

        $response = $this->updateCartRoute->updateCart('CART-11111111111111111111111111111111', new Request(content: $content), $this->salesChannelContext);

        static::assertSame(200, $response->getStatusCode());
        static::assertSame(PayPalCart::STATUS__READY, $response->getObject()->offsetGet('validation_status'));
    }

    public function testUpsertAddresses(): void
    {
        $content = json_encode(array_merge(self::createItems(), self::createCustomer()));
        static::assertIsString($content);

        $customer = new CustomerEntity();
        $customer->setId(Uuid::randomHex());
        $customer->setDefaultShippingAddressId(Uuid::randomHex());
        $customer->setDefaultBillingAddressId(Uuid::randomHex());

        $this->salesChannelContext
            ->method('getCustomer')
            ->willReturn($customer);

        $this->customerAddressRepository
            ->expects($this->never())
            ->method('delete');

        $this->cartService
            ->expects($this->once())
            ->method('deleteCart');

        $customerData = [
            'customerData' => 'value',
            'shippingAddress' => [
                'some' => 'data',
            ],
            'billingAddress' => [
                'other' => 'data',
            ],
        ];

        $upsertData = [
            'id' => $customer->getId(),
            'customerData' => 'value',
            'defaultShippingAddress' => [
                'id' => $customer->getDefaultShippingAddressId(),
                'some' => 'data',
            ],
            'defaultBillingAddress' => [
                'id' => $customer->getDefaultBillingAddressId(),
                'other' => 'data',
            ],
        ];

        $this->customerRepository
            ->expects($this->never())
            ->method('delete');
        $this->customerRepository
            ->expects($this->once())
            ->method('update')
            ->with([$upsertData]);

        $this->shopwareCartTransformer
            ->expects($this->once())
            ->method('extractCustomerData')
            ->willReturn($customerData);

        $payPalCart = new PayPalCart();
        $payPalCart->setValidationStatus(PayPalCart::VALIDATION_STATUS__VALID);
        $createResponse = new AgentCartResponse($payPalCart);

        $this->createCartRoute
            ->expects($this->once())
            ->method('createCart')
            ->willReturn($createResponse);

        $response = $this->updateCartRoute->updateCart('CART-11111111111111111111111111111111', new Request(content: $content), $this->salesChannelContext);

        static::assertSame(200, $response->getStatusCode());
        static::assertSame(PayPalCart::STATUS__READY, $response->getObject()->offsetGet('validation_status'));
    }

    public function testDeleteBillingAddress(): void
    {
        $content = json_encode(array_merge(self::createItems(), self::createCustomer()));
        static::assertIsString($content);

        $customer = new CustomerEntity();
        $customer->setId(Uuid::randomHex());
        $customer->setDefaultShippingAddressId(Uuid::randomHex());
        $customer->setDefaultBillingAddressId(Uuid::randomHex());

        $this->salesChannelContext
            ->method('getCustomer')
            ->willReturn($customer);

        $this->customerAddressRepository
            ->expects($this->once())
            ->method('delete')
            ->with([['id' => $customer->getDefaultBillingAddressId()]]);

        $this->cartService
            ->expects($this->once())
            ->method('deleteCart');

        $customerData = [
            'customerData' => 'value',
            'shippingAddress' => [
                'some' => 'data',
            ],
        ];

        $upsertData = [
            'id' => $customer->getId(),
            'customerData' => 'value',
            'defaultShippingAddress' => [
                'id' => $customer->getDefaultShippingAddressId(),
                'some' => 'data',
            ],
            'defaultBillingAddressId' => $customer->getDefaultShippingAddressId(),
        ];

        $this->customerRepository
            ->expects($this->never())
            ->method('delete');
        $this->customerRepository
            ->expects($this->once())
            ->method('update')
            ->with([$upsertData]);

        $this->shopwareCartTransformer
            ->method('extractCustomerData')
            ->willReturn($customerData);

        $payPalCart = new PayPalCart();
        $payPalCart->setValidationStatus(PayPalCart::VALIDATION_STATUS__VALID);
        $createResponse = new AgentCartResponse($payPalCart);

        $this->createCartRoute
            ->expects($this->once())
            ->method('createCart')
            ->willReturn($createResponse);

        $response = $this->updateCartRoute->updateCart('CART-11111111111111111111111111111111', new Request(content: $content), $this->salesChannelContext);

        static::assertSame(200, $response->getStatusCode());
        static::assertSame(PayPalCart::STATUS__READY, $response->getObject()->offsetGet('validation_status'));
    }

    public function testChangeShippingMethod(): void
    {
        $content = json_encode(array_merge(self::createItems(), self::createShippingOptions()));
        static::assertIsString($content);

        $this->salesChannelContext
            ->method('getCustomer')
            ->willReturn(null);

        $this->cartService
            ->expects($this->once())
            ->method('deleteCart');

        $payPalCart = new PayPalCart();
        $payPalCart->setValidationStatus(PayPalCart::VALIDATION_STATUS__VALID);
        $createResponse = new AgentCartResponse($payPalCart);

        $this->createCartRoute
            ->expects($this->once())
            ->method('createCart')
            ->willReturn($createResponse);

        $this->contextSwitchRoute
            ->expects($this->once())
            ->method('switchContext')
            ->willReturn(new ContextTokenResponse('some-token', 'some-url'));

        $response = $this->updateCartRoute->updateCart('CART-11111111111111111111111111111111', new Request(content: $content), $this->salesChannelContext);

        static::assertSame(200, $response->getStatusCode());
        static::assertSame(PayPalCart::STATUS__READY, $response->getObject()->offsetGet('validation_status'));
    }

    public function testShippingMethodError(): void
    {
        $this->expectException(AgentException::class);

        $content = json_encode(array_merge(self::createItems(), self::createShippingOptions()));
        static::assertIsString($content);

        $this->salesChannelContext
            ->method('getCustomer')
            ->willReturn(null);

        $this->cartService
            ->expects($this->once())
            ->method('deleteCart');

        $this->createCartRoute
            ->expects($this->never())
            ->method('createCart');

        $this->contextSwitchRoute
            ->expects($this->once())
            ->method('switchContext')
            ->willThrowException(new ConstraintViolationException(new ConstraintViolationList(), []));

        $response = $this->updateCartRoute->updateCart('CART-11111111111111111111111111111111', new Request(content: $content), $this->salesChannelContext);

        static::assertSame(200, $response->getStatusCode());
        static::assertSame(PayPalCart::STATUS__READY, $response->getObject()->offsetGet('validation_status'));
    }

    public function testRightShippingMethodSelected(): void
    {
        $options = self::createShippingOptions();

        $content = json_encode(array_merge(self::createItems(), $options));
        static::assertIsString($content);

        $shippingMethod = new ShippingMethodEntity();
        $shippingMethod->setId($options['available_shipping_options'][1]['id']);

        $this->salesChannelContext
            ->method('getCustomer')
            ->willReturn(null);
        $this->salesChannelContext
            ->method('getShippingMethod')
            ->willReturn($shippingMethod);

        $this->cartService
            ->expects($this->once())
            ->method('deleteCart');

        $payPalCart = new PayPalCart();
        $payPalCart->setValidationStatus(PayPalCart::VALIDATION_STATUS__VALID);
        $createResponse = new AgentCartResponse($payPalCart);

        $this->createCartRoute
            ->expects($this->once())
            ->method('createCart')
            ->willReturn($createResponse);

        $this->contextSwitchRoute
            ->expects($this->never())
            ->method('switchContext');

        $response = $this->updateCartRoute->updateCart('CART-11111111111111111111111111111111', new Request(content: $content), $this->salesChannelContext);

        static::assertSame(200, $response->getStatusCode());
        static::assertSame(PayPalCart::STATUS__READY, $response->getObject()->offsetGet('validation_status'));
    }

    private static function createItems(): array
    {
        return ['items' => [['variant_id' => Uuid::randomHex(), 'quantity' => 1]]];
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

    private static function createShippingOptions(): array
    {
        return [
            'available_shipping_options' => [
                ['id' => Uuid::randomHex(), 'is_selected' => false],
                ['id' => Uuid::randomHex(), 'is_selected' => true],
            ],
        ];
    }
}
