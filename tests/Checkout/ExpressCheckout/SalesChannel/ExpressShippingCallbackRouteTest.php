<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\ExpressCheckout\SalesChannel;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\CheckoutException;
use Swag\PayPal\Checkout\ExpressCheckout\SalesChannel\ExpressShippingCallbackRoute;
use Swag\PayPal\Checkout\ExpressCheckout\Service\ExpressShippingCallbackService;
use Swag\PayPal\Test\Helper\SalesChannelContextTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
#[Package('checkout')]
class ExpressShippingCallbackRouteTest extends TestCase
{
    use IntegrationTestBehaviour;
    use SalesChannelContextTrait;

    public function testHandleCallbackWithValidPayload(): void
    {
        $service = $this->createMock(ExpressShippingCallbackService::class);
        $service->expects($this->once())
            ->method('recalculateCart')
            ->with(
                'ORDER-123',
                ['country_code' => 'AT'],
                static::isInstanceOf(SalesChannelContext::class)
            )
            ->willReturn([[
                'amount' => [
                    'currency_code' => 'EUR',
                    'value' => '120.00',
                    'breakdown' => [
                        'item_total' => ['currency_code' => 'EUR', 'value' => '100.00'],
                        'shipping' => ['currency_code' => 'EUR', 'value' => '0.00'],
                        'tax_total' => ['currency_code' => 'EUR', 'value' => '20.00'],
                    ],
                ],
            ]]);

        $route = new ExpressShippingCallbackRoute($service, new NullLogger());

        $request = new Request([], [
            'id' => 'ORDER-123',
            'shipping_address' => ['country_code' => 'AT'],
        ]);

        $response = $route->handleCallback($request, $this->getSalesChannelContext());

        static::assertSame(Response::HTTP_OK, $response->getStatusCode());
        $responseContent = $response->getContent();
        static::assertNotFalse($responseContent);
        $content = \json_decode($responseContent, true);
        static::assertIsArray($content);
        static::assertArrayHasKey('purchase_units', $content);
        static::assertSame('120.00', $content['purchase_units'][0]['amount']['value']);
    }

    public function testHandleCallbackWithMissingOrderId(): void
    {
        $service = $this->createMock(ExpressShippingCallbackService::class);
        $service->expects($this->never())->method('recalculateCart');

        $route = new ExpressShippingCallbackRoute($service, new NullLogger());

        $request = new Request([], [
            'shipping_address' => ['country_code' => 'AT'],
        ]);

        $response = $route->handleCallback($request, $this->getSalesChannelContext());

        static::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testHandleCallbackWithMissingShippingAddress(): void
    {
        $service = $this->createMock(ExpressShippingCallbackService::class);
        $service->expects($this->never())->method('recalculateCart');

        $route = new ExpressShippingCallbackRoute($service, new NullLogger());

        $request = new Request([], [
            'id' => 'ORDER-123',
        ]);

        $response = $route->handleCallback($request, $this->getSalesChannelContext());

        static::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testHandleCallbackWithMissingCountryCodeException(): void
    {
        $service = $this->createMock(ExpressShippingCallbackService::class);
        $service->expects($this->once())
            ->method('recalculateCart')
            ->with(
                'ORDER-123',
                ['country_code' => null],
                static::isInstanceOf(SalesChannelContext::class)
            )
            ->willThrowException(CheckoutException::expressMissingCountryCode());

        $route = new ExpressShippingCallbackRoute($service, new NullLogger());

        $request = new Request([], [
            'id' => 'ORDER-123',
            'shipping_address' => ['country_code' => 'AT'],
        ]);

        $response = $route->handleCallback($request, $this->getSalesChannelContext());

        static::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
    }

    private function getSalesChannelContext(): SalesChannelContext
    {
        return $this->createSalesChannelContext(
            $this->getContainer(),
            new PaymentMethodCollection(),
        );
    }
}
