<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\AgentCommerce\SalesChannel;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\LineItemFactoryHandler\ProductLineItemFactory;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Customer\SalesChannel\AbstractRegisterRoute;
use Shopware\Core\Checkout\Promotion\Cart\PromotionItemBuilder;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\Coupon;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\PayPalCart;
use Shopware\PayPalSDK\Struct\V2\Order;
use Swag\PayPal\AgentCommerce\SalesChannel\CreateCartRoute;
use Swag\PayPal\AgentCommerce\Util\PayPalCartTransformer;
use Swag\PayPal\AgentCommerce\Util\ShopwareCartTransformer;
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

    private ProductLineItemFactory&MockObject $lineItemFactory;

    private AbstractOrderBuilder&MockObject $orderBuilder;

    private OrderResource&MockObject $orderResource;

    private PromotionItemBuilder&MockObject $promotionItemBuilder;

    private CreateCartRoute $createCartRoute;

    protected function setUp(): void
    {
        $this->contextService = $this->createMock(SalesChannelContextService::class);
        $this->cartService = $this->createMock(CartService::class);
        $this->payPalCartTransformer = $this->createMock(PayPalCartTransformer::class);
        $this->shopwareCartTransformer = $this->createMock(ShopwareCartTransformer::class);
        $this->registerRoute = $this->createMock(AbstractRegisterRoute::class);
        $this->lineItemFactory = $this->createMock(ProductLineItemFactory::class);
        $this->orderBuilder = $this->createMock(AbstractOrderBuilder::class);
        $this->orderResource = $this->createMock(OrderResource::class);
        $this->promotionItemBuilder = $this->createMock(PromotionItemBuilder::class);

        $this->createCartRoute = new CreateCartRoute(
            $this->contextService,
            $this->cartService,
            $this->payPalCartTransformer,
            $this->shopwareCartTransformer,
            $this->registerRoute,
            $this->lineItemFactory,
            $this->orderBuilder,
            $this->orderResource,
            $this->promotionItemBuilder
        );
    }

    public function testCreateCartWithCreateAndLoginCustomer(): void
    {
        $cartData = array_merge(self::createItems(), self::createCustomer(), self::createCoupons());

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

        $this->cartService
            ->expects($this->once())
            ->method('createNew')
            ->willReturn($cart);
        $this->cartService
            ->expects($this->once())
            ->method('add')
            ->willReturn($cart);

        $this->promotionItemBuilder
            ->expects($this->once())
            ->method('buildPlaceholderItem')
            ->willReturn(new LineItem(Uuid::randomHex(), 'promotion'));

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

    private static function createCoupons(): array
    {
        return ['coupons' => [['action' => Coupon::APPLY, 'code' => 'some-code']]];
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
