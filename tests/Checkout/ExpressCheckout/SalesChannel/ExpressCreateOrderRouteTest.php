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
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Swag\PayPal\Checkout\ExpressCheckout\SalesChannel\ExpressCreateOrderRoute;
use Swag\PayPal\OrdersApi\Builder\OrderFromCartBuilder;
use Swag\PayPal\OrdersApi\Builder\Util\AddressProvider;
use Swag\PayPal\OrdersApi\Builder\Util\AmountProvider;
use Swag\PayPal\OrdersApi\Builder\Util\PurchaseUnitProvider;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Test\Helper\CheckoutRouteTrait;
use Swag\PayPal\Test\Mock\EventDispatcherMock;
use Swag\PayPal\Test\Mock\LoggerMock;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\CreateOrderCapture;
use Swag\PayPal\Util\PriceFormatter;
use Symfony\Component\HttpFoundation\Response;

class ExpressCreateOrderRouteTest extends TestCase
{
    use CheckoutRouteTrait;
    use IntegrationTestBehaviour;

    public function testCreatePayment(): void
    {
        $salesChannelContext = $this->getSalesChannelContext();

        $response = $this->createRoute()->createPayPalOrder($salesChannelContext);

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

        $orderFromCartBuilder = new OrderFromCartBuilder(
            $priceFormatter,
            $systemConfig,
            new PurchaseUnitProvider($amountProvider, $addressProvider, $systemConfig),
            $addressProvider,
            new EventDispatcherMock(),
            new LoggerMock()
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
