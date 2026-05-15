<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\AgenticCommerce\SalesChannel;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\PayPalCart;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\ValidationIssue;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\ValidationIssueCollection;
use Swag\PayPal\AgenticCommerce\Exception\AgentException;
use Swag\PayPal\AgenticCommerce\SalesChannel\GetCartRoute;
use Swag\PayPal\AgenticCommerce\Util\PayPalCartTransformer;

/**
 * @internal
 */
#[CoversClass(GetCartRoute::class)]
#[Package('checkout')]
class GetCartRouteTest extends TestCase
{
    public function testInvalidCartToken(): void
    {
        $this->expectException(AgentException::class);
        $this->expectExceptionMessage('Cart ID format is invalid. Expected format: CART-[a-zA-Z0-9]{32}');

        $getCartRoute = new GetCartRoute(
            $this->createMock(CartService::class),
            $this->createMock(PayPalCartTransformer::class)
        );

        $getCartRoute->getCart('invalid-token', $this->createMock(SalesChannelContext::class));
    }

    public function testEmptyCart(): void
    {
        $token = 'CART-11111111111111111111111111111111';

        $this->expectException(AgentException::class);
        $this->expectExceptionMessage('Cart with ID \'' . $token . '\' does not exist');

        $cartService = $this->createMock(CartService::class);
        $cartService
            ->method('getCart')
            ->willReturn(new Cart('token'));

        $getCartRoute = new GetCartRoute(
            $cartService,
            $this->createMock(PayPalCartTransformer::class)
        );

        $getCartRoute->getCart($token, $this->createMock(SalesChannelContext::class));
    }

    public function testCartWithValidationIssues(): void
    {
        $token = 'CART-11111111111111111111111111111111';

        $cart = new Cart('token');
        $cart->add(new LineItem(Uuid::randomHex(), 'test'));

        $cartService = $this->createMock(CartService::class);
        $cartService
            ->method('getCart')
            ->willReturn($cart);

        $payPalCart = new PayPalCart();
        $payPalCart->setValidationStatus(PayPalCart::VALIDATION_STATUS__INVALID);
        $payPalCart->setValidationIssues(new ValidationIssueCollection([new ValidationIssue()]));

        $cartTransformer = $this->createMock(PayPalCartTransformer::class);
        $cartTransformer
            ->method('convertToPayPalCart')
            ->willReturn($payPalCart);

        $getCartRoute = new GetCartRoute($cartService, $cartTransformer);

        $response = $getCartRoute->getCart($token, $this->createMock(SalesChannelContext::class));

        static::assertSame(PayPalCart::STATUS__INCOMPLETE, $response->getCart()->getStatus());
    }

    public function testValidCart(): void
    {
        $token = 'CART-11111111111111111111111111111111';

        $cart = new Cart('token');
        $cart->add(new LineItem(Uuid::randomHex(), 'test'));

        $cartService = $this->createMock(CartService::class);
        $cartService
            ->method('getCart')
            ->willReturn($cart);

        $payPalCart = new PayPalCart();
        $payPalCart->setValidationStatus(PayPalCart::VALIDATION_STATUS__VALID);

        $cartTransformer = $this->createMock(PayPalCartTransformer::class);
        $cartTransformer
            ->method('convertToPayPalCart')
            ->willReturn($payPalCart);

        $getCartRoute = new GetCartRoute($cartService, $cartTransformer);

        $response = $getCartRoute->getCart($token, $this->createMock(SalesChannelContext::class));

        static::assertSame(PayPalCart::STATUS__READY, $response->getCart()->getStatus());
    }
}
