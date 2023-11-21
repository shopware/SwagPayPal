<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\ExpressCheckout\SalesChannel;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Swag\PayPal\Checkout\ExpressCheckout\SalesChannel\ExpressCreateOrderRoute;
use Swag\PayPal\Checkout\Payment\Service\VaultTokenService;
use Swag\PayPal\OrdersApi\Builder\OrderFromCartBuilder;
use Swag\PayPal\OrdersApi\Builder\Util\AddressProvider;
use Swag\PayPal\OrdersApi\Builder\Util\AmountProvider;
use Swag\PayPal\OrdersApi\Builder\Util\PurchaseUnitProvider;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Test\Helper\CheckoutRouteTrait;
use Swag\PayPal\Test\Mock\CustomIdProviderMock;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\CreateOrderCapture;
use Swag\PayPal\Util\LocaleCodeProvider;
use Swag\PayPal\Util\PriceFormatter;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
#[Package('checkout')]
class ExpressCreateOrderRouteTest extends TestCase
{
    use CheckoutRouteTrait;
    use IntegrationTestBehaviour;

    public function testCreatePayment(): void
    {
        $salesChannelContext = $this->getSalesChannelContext();

        $response = $this->createRoute()->createPayPalOrder(new Request(), $salesChannelContext);

        static::assertSame(Response::HTTP_OK, $response->getStatusCode());
        static::assertSame(CreateOrderCapture::ID, $response->getToken());
    }

    private function createRoute(): ExpressCreateOrderRoute
    {
        $systemConfig = $this->createSystemConfigServiceMock([
            Settings::CLIENT_ID => 'testClientId',
            Settings::CLIENT_SECRET => 'testClientSecret',
        ]);

        $priceFormatter = new PriceFormatter();
        $amountProvider = new AmountProvider($priceFormatter);
        $addressProvider = new AddressProvider();
        $customIdProvider = new CustomIdProviderMock();

        $orderFromCartBuilder = new OrderFromCartBuilder(
            $priceFormatter,
            $systemConfig,
            new PurchaseUnitProvider($amountProvider, $addressProvider, $customIdProvider, $systemConfig),
            $addressProvider,
            $this->createMock(LocaleCodeProvider::class),
            $this->createMock(EventDispatcherInterface::class),
            new NullLogger(),
            $this->createMock(VaultTokenService::class),
        );

        $orderResource = $this->createOrderResource($systemConfig);

        return new ExpressCreateOrderRoute(
            $this->getContainer()->get(CartService::class),
            $orderFromCartBuilder,
            $orderResource,
            new NullLogger()
        );
    }
}
