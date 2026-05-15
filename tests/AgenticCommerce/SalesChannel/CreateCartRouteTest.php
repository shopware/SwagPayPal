<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\AgenticCommerce\SalesChannel;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Customer\SalesChannel\AbstractRegisterRoute;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\PayPalCart;
use Shopware\PayPalSDK\Struct\V2\Order;
use Swag\PayPal\AgenticCommerce\SalesChannel\CreateCartRoute;
use Swag\PayPal\AgenticCommerce\Util\PayPalCartTransformer;
use Swag\PayPal\AgenticCommerce\Util\ShopwareCartTransformer;
use Swag\PayPal\OrdersApi\Builder\AbstractOrderBuilder;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[CoversClass(CreateCartRoute::class)]
#[Package('checkout')]
class CreateCartRouteTest extends TestCase
{
    private SalesChannelContextService&MockObject $contextService;

    private CartService&MockObject $cartService;

    private PayPalCartTransformer&MockObject $payPalCartTransformer;

    private ShopwareCartTransformer&MockObject $shopwareCartTransformer;

    private AbstractRegisterRoute&MockObject $registerRoute;

    private AbstractOrderBuilder&MockObject $orderBuilder;

    private OrderResource&MockObject $orderResource;

    private CreateCartRoute $createCartRoute;

    protected function setUp(): void
    {
        $this->contextService = $this->createMock(SalesChannelContextService::class);
        $this->cartService = $this->createMock(CartService::class);
        $this->payPalCartTransformer = $this->createMock(PayPalCartTransformer::class);
        $this->shopwareCartTransformer = $this->createMock(ShopwareCartTransformer::class);
        $this->registerRoute = $this->createMock(AbstractRegisterRoute::class);
        $this->orderBuilder = $this->createMock(AbstractOrderBuilder::class);
        $this->orderResource = $this->createMock(OrderResource::class);

        $this->createCartRoute = new CreateCartRoute(
            $this->contextService,
            $this->cartService,
            $this->payPalCartTransformer,
            $this->shopwareCartTransformer,
            $this->registerRoute,
            $this->orderBuilder,
            $this->orderResource,
        );
    }

    public function testCreateCartWithCreateAndLoginCustomer(): void
    {
        $cartData = array_merge(self::createItems(), self::createCustomer());

        $salesChannelContext = $this->createMock(SalesChannelContext::class);
        $salesChannelContext
            ->method('getCustomer')
            ->willReturn(null);

        $this->shopwareCartTransformer
            ->expects($this->once())
            ->method('extractCustomerData')
            ->willReturn(['valid-data']);

        $this->registerRoute
            ->expects($this->once())
            ->method('register');

        $this->contextService
            ->method('get')
            ->willReturn($salesChannelContext);

        $cart = new Cart('');
        $cart->add(new LineItem(Uuid::randomHex(), 'product', Uuid::randomHex()));

        $this->cartService
            ->expects($this->once())
            ->method('createNew')
            ->willReturn($cart);
        $this->cartService
            ->expects($this->once())
            ->method('add')
            ->willReturn($cart);

        $this->shopwareCartTransformer
            ->expects($this->once())
            ->method('getLineItems')
            ->willReturn([]);

        $order = new Order();
        $order->setId('some-order-id');

        $this->orderBuilder
            ->expects($this->once())
            ->method('getOrderFromCart')
            ->willReturn($order);

        $this->orderResource
            ->expects($this->once())
            ->method('create')
            ->willReturn($order);

        $payPalCart = new PayPalCart();
        $payPalCart->setValidationStatus(PayPalCart::VALIDATION_STATUS__VALID);

        $this->payPalCartTransformer
            ->expects($this->once())
            ->method('convertToPayPalCart')
            ->willReturn($payPalCart);

        $content = json_encode($cartData);
        static::assertIsString($content);

        $response = $this->createCartRoute->createCart(new Request(content: $content), $salesChannelContext);

        static::assertSame(PayPalCart::STATUS__CREATED, $response->getCart()->getStatus());
        static::assertSame(PayPalCart::VALIDATION_STATUS__VALID, $response->getCart()->getValidationStatus());
        static::assertSame('some-order-id', $response->getCart()->getPaymentMethod()?->getToken());
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
}
