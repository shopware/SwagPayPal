<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\ExpressCheckout\SalesChannel;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Swag\PayPal\Checkout\ExpressCheckout\SalesChannel\ExpressCreateOrderRoute;
use Swag\PayPal\OrdersApi\Builder\OrderFromCartBuilder;
use Swag\PayPal\OrdersApi\Builder\Util\AmountProvider;
use Swag\PayPal\Setting\SwagPayPalSettingStruct;
use Swag\PayPal\Test\Helper\CheckoutRouteTrait;
use Swag\PayPal\Test\Mock\EventDispatcherMock;
use Swag\PayPal\Test\Mock\LoggerMock;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\CreateOrderCapture;
use Swag\PayPal\Test\Mock\Setting\Service\SettingsServiceMock;
use Swag\PayPal\Util\PriceFormatter;
use Symfony\Component\HttpFoundation\Response;

class ExpressCreateOrderRouteTest extends TestCase
{
    use CheckoutRouteTrait;

    public function testCreatePayment(): void
    {
        $salesChannelContext = $this->getSalesChannelContext();

        $response = $this->createRoute()->createPayPalOrder($salesChannelContext);

        static::assertSame(Response::HTTP_OK, $response->getStatusCode());
        static::assertSame(CreateOrderCapture::ID, $response->getToken());
    }

    private function createRoute(): ExpressCreateOrderRoute
    {
        $settings = new SwagPayPalSettingStruct();
        $settings->setClientId('testClientId');
        $settings->setClientSecret('testClientSecret');

        $settingsService = new SettingsServiceMock($settings);

        $priceFormatter = new PriceFormatter();
        $orderFromCartBuilder = new OrderFromCartBuilder(
            $settingsService,
            $priceFormatter,
            new AmountProvider($priceFormatter),
            new EventDispatcherMock(),
            new LoggerMock()
        );
        /** @var CartService $cartService */
        $cartService = $this->getContainer()->get(CartService::class);

        $orderResource = $this->createOrderResource($settings);

        return new ExpressCreateOrderRoute(
            $cartService,
            $orderFromCartBuilder,
            $orderResource
        );
    }
}
