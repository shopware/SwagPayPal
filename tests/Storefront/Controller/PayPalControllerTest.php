<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Storefront\Controller;

use Monolog\Level;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\SalesChannel\AbstractCartDeleteRoute;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannel\AbstractContextSwitchRoute;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Test\Generator;
use Swag\PayPal\Checkout\Cart\Service\CartPriceService;
use Swag\PayPal\Checkout\ExpressCheckout\SalesChannel\AbstractExpressCreateOrderRoute;
use Swag\PayPal\Checkout\ExpressCheckout\SalesChannel\AbstractExpressPrepareCheckoutRoute;
use Swag\PayPal\Checkout\ExpressCheckout\SalesChannel\AbstractExpressShippingCallbackRoute;
use Swag\PayPal\Checkout\PUI\SalesChannel\AbstractPUIPaymentInstructionsRoute;
use Swag\PayPal\Checkout\SalesChannel\AbstractClearVaultRoute;
use Swag\PayPal\Checkout\SalesChannel\AbstractCreateOrderRoute;
use Swag\PayPal\Checkout\SalesChannel\AbstractMethodEligibilityRoute;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\Storefront\Controller\PayPalController;
use Swag\PayPal\Test\Helper\PayPalLogTrait;
use Swag\PayPal\Util\Log\Log;
use Swag\PayPal\Util\Log\LogCode;
use Swag\PayPal\Util\PriceFormatter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * @internal
 */
#[CoversClass(PayPalController::class)]
#[Package('checkout')]
class PayPalControllerTest extends TestCase
{
    use PayPalLogTrait;

    private AbstractCreateOrderRoute&MockObject $createOrderRoute;

    private AbstractClearVaultRoute&MockObject $clearVaultRoute;

    private PayPalController&MockObject $controller;

    protected function setUp(): void
    {
        $this->createOrderRoute = $this->createMock(AbstractCreateOrderRoute::class);
        $this->clearVaultRoute = $this->createMock(AbstractClearVaultRoute::class);

        $this->controller = $this->getMockBuilder(PayPalController::class)
            ->onlyMethods(['trans', 'addFlash'])
            ->setConstructorArgs([
                $this->createOrderRoute,
                $this->createMock(AbstractMethodEligibilityRoute::class),
                $this->createMock(AbstractPUIPaymentInstructionsRoute::class),
                $this->createMock(AbstractExpressPrepareCheckoutRoute::class),
                $this->createMock(AbstractExpressCreateOrderRoute::class),
                $this->createMock(AbstractExpressShippingCallbackRoute::class),
                $this->createMock(AbstractContextSwitchRoute::class),
                $this->createMock(AbstractCartDeleteRoute::class),
                $this->createMock(CartService::class),
                new CartPriceService(new PriceFormatter()),
                $this->clearVaultRoute,
                $this->createPayPalLogger(),
            ])
            ->getMock();
    }

    public function testCreateOrderWillReturnErrorResponseOnThrownPayPalApiException(): void
    {
        $exception = new PayPalApiException('test', 'message', issue: 'issue');

        $this->createOrderRoute
            ->expects(static::once())
            ->method('createPayPalOrder')
            ->willThrowException($exception);

        $response = $this->controller->createOrder($this->generateSalesChannelContext(), new Request());

        static::assertIsString($response->getContent());
        $json = \json_decode($response->getContent(), true);
        static::assertIsArray($json);

        $errors = $json['errors'];
        static::assertCount(1, $errors);
        static::assertSame('SWAG_PAYPAL__API_issue', $json['errors'][0]['code']);
    }

    public function testOnHandleErrorWithTranslatableErrorCodeAddsFlash(): void
    {
        $request = new Request(request: [
            'code' => 'SWAG_PAYPAL__TRANSLATABLE_ERROR_CODE',
            'isCheckout' => true,
        ]);

        $matcher = static::exactly(2);
        $this->controller
            ->expects($matcher)
            ->method('trans')
            ->willReturnCallback(static function (string $key) use (&$matcher) {
                match ($matcher->numberOfInvocations()) {
                    1 => static::assertSame('paypal.error.SWAG_PAYPAL__TRANSLATABLE_ERROR_CODE', $key),
                    2 => static::assertSame('paypal.error.test_handler.SWAG_PAYPAL__TRANSLATABLE_ERROR_CODE', $key),
                    default => static::fail('Unexpected number of invocations'),
                };

                return 'Translated error message';
            });

        $this->controller
            ->expects(static::once())
            ->method('addFlash')
            ->with('danger', 'Translated error message');

        $this->controller->onHandleError($request, $this->generateSalesChannelContext());

        $this->assertPayPalLogged(LogCode::STOREFRONT_ERROR_REPORTED, Level::Warning, [Log::CLIENT_CODE => 'SWAG_PAYPAL__TRANSLATABLE_ERROR_CODE', Log::FATAL => false]);
    }

    public function testOnHandleErrorWithTranslatableErrorCodeSkipsFlash(): void
    {
        $request = new Request(request: [
            'code' => 'SWAG_PAYPAL__TRANSLATABLE_ERROR_CODE',
            'isCheckout' => false,
        ]);

        $this->controller
            ->expects(static::never())
            ->method('trans');

        $this->controller
            ->expects(static::never())
            ->method('addFlash');

        $this->controller->onHandleError($request, $this->generateSalesChannelContext());

        $this->assertPayPalLogged(LogCode::STOREFRONT_ERROR_REPORTED, Level::Warning, [Log::CLIENT_CODE => 'SWAG_PAYPAL__TRANSLATABLE_ERROR_CODE', Log::FATAL => false]);
    }

    public function testOnHandleErrorWithNonTranslatableErrorCodeAddsFlash(): void
    {
        $request = new Request(request: [
            'code' => 'SWAG_PAYPAL__NON_TRANSLATABLE_ERROR_CODE',
            'isCheckout' => true,
        ]);

        $this->controller
            ->expects(static::exactly(3))
            ->method('trans')
            ->willReturnCallback(static fn (string $key) => $key);

        $this->controller
            ->expects(static::once())
            ->method('addFlash')
            ->with('danger', 'paypal.error.SWAG_PAYPAL__GENERIC_ERROR');

        $this->controller->onHandleError($request, $this->generateSalesChannelContext());

        $this->assertPayPalLogged(LogCode::STOREFRONT_ERROR_REPORTED, Level::Warning, [Log::CLIENT_CODE => 'SWAG_PAYPAL__NON_TRANSLATABLE_ERROR_CODE', Log::FATAL => false]);
    }

    public function testOnHandleErrorWithNonTranslatableErrorCodeSkipsFlash(): void
    {
        $request = new Request(request: [
            'code' => 'SWAG_PAYPAL__NON_TRANSLATABLE_ERROR_CODE',
            'isCheckout' => false,
        ]);

        $this->controller
            ->expects(static::never())
            ->method('trans');

        $this->controller
            ->expects(static::never())
            ->method('addFlash');

        $this->controller->onHandleError($request, $this->generateSalesChannelContext());

        $this->assertPayPalLogged(LogCode::STOREFRONT_ERROR_REPORTED, Level::Warning, [Log::CLIENT_CODE => 'SWAG_PAYPAL__NON_TRANSLATABLE_ERROR_CODE', Log::FATAL => false]);
    }

    #[DataProvider('onHandleErrorDataProvider')]
    public function testOnHandleError(string $code, bool $fatal, Level $level, ?string $logCode, string $message, ?string $step = null): void
    {
        $salesChannelContext = $this->generateSalesChannelContext();
        $request = new Request(request: [
            'code' => $code,
            'fatal' => $fatal,
            'step' => $step,
            'pageType' => 'checkout',
            'error' => 'Something happened',
        ]);

        $session = new Session(new MockArraySessionStorage());
        $request->setSession($session);

        $this->controller->onHandleError($request, $salesChannelContext);

        static::assertSame(
            $fatal ? $salesChannelContext->getPaymentMethod()->getId() : null,
            $session->get(PayPalController::PAYMENT_METHOD_FATAL_ERROR),
        );

        $this->assertPayPalLogged($logCode, $level, [
            Log::PAGE_TYPE => 'checkout',
            Log::PAYMENT_METHOD => 'swag_paypal_test',
            Log::CLIENT_CODE => $code,
            Log::FATAL => $fatal,
            Log::CLIENT_ERROR => 'Something happened',
        ], $message);
    }

    public static function onHandleErrorDataProvider(): \Generator
    {
        $cancelled = 'The browser reported that the buyer closed the PayPal window';
        $declined = 'The browser reported that PayPal declined the payment method';
        $script = 'The browser reported that the PayPal script failed';
        $reported = 'The browser reported a PayPal error';
        $unavailable = 'The browser reported that PayPal is unavailable for this buyer';

        foreach ([true, false] as $fatal) {
            $suffix = $fatal ? ' (fatal)' : '';
            yield 'user cancelled' . $suffix => ['SWAG_PAYPAL__USER_CANCELLED', $fatal, Level::Notice, LogCode::STOREFRONT_BUYER_CANCELLED, $cancelled];
            yield 'express user cancelled' . $suffix => ['SWAG_PAYPAL__EXPRESS_USER_CANCELLED', $fatal, Level::Notice, LogCode::STOREFRONT_BUYER_CANCELLED, $cancelled];
            yield 'declined' . $suffix => ['SWAG_PAYPAL__DECLINED_ERROR', $fatal, Level::Notice, LogCode::STOREFRONT_PAYMENT_DECLINED, $declined];
            yield 'script error' . $suffix => ['SWAG_PAYPAL__SCRIPT_ERROR', $fatal, Level::Warning, LogCode::STOREFRONT_SCRIPT_FAILED, $script];
            yield 'script not loaded' . $suffix => ['SWAG_PAYPAL__SCRIPT_NOT_LOADED', $fatal, Level::Warning, LogCode::STOREFRONT_SCRIPT_FAILED, $script];
            yield 'generic error' . $suffix => ['SWAG_PAYPAL__GENERIC_ERROR', $fatal, Level::Warning, LogCode::STOREFRONT_ERROR_REPORTED, $reported];
            yield 'script load failed' . $suffix => ['SWAG_PAYPAL__GENERIC_ERROR', $fatal, Level::Warning, LogCode::STOREFRONT_SCRIPT_FAILED, $script, 'SCRIPT_LOAD'];
            yield 'browser unsupported' . $suffix => ['SWAG_PAYPAL__BROWSER_UNSUPPORTED', $fatal, Level::Debug, null, $unavailable];
            yield 'not eligible' . $suffix => ['SWAG_PAYPAL__NOT_ELIGIBLE', $fatal, Level::Debug, null, $unavailable];
        }
    }

    public function testOnHandleErrorWithUnknownBrowserValues(): void
    {
        $request = new Request(request: [
            'code' => 'EVIL {pageType} CODE',
            Log::PAGE_TYPE => 'checkout {code} <script>',
            'error' => \str_repeat('e', 300),
        ]);
        $request->setSession(new Session(new MockArraySessionStorage()));

        $this->controller->onHandleError($request, $this->generateSalesChannelContext());

        $this->assertPayPalLogged(LogCode::STOREFRONT_ERROR_REPORTED, Level::Warning, [
            Log::PAGE_TYPE => 'checkout {code} <script>',
            Log::CLIENT_CODE => 'EVIL {pageType} CODE',
            Log::CLIENT_ERROR => \str_repeat('e', 200),
        ], 'The browser reported a PayPal error');
    }

    #[DataProvider('addErrorMessageDataProvider')]
    public function testAddErrorMessage(Request $request, Level $level, ?string $logCode, string $message): void
    {
        $this->controller->addErrorMessage($request);

        $record = $this->assertPayPalLogged($logCode, $level, [], $message);
        static::assertSame('Something happened', $record->context[Log::CLIENT_ERROR] ?? null);
    }

    public static function addErrorMessageDataProvider(): \Generator
    {
        yield 'cancel' => [
            new Request(request: ['type' => 'cancel', 'error' => 'Something happened']),
            Level::Notice,
            LogCode::STOREFRONT_BUYER_CANCELLED,
            'The browser reported that the buyer closed the PayPal window',
        ];
        yield 'browser unsupported' => [
            new Request(request: ['type' => 'browser', 'error' => 'Something happened']),
            Level::Debug,
            null,
            'The browser reported that PayPal is unavailable for this buyer',
        ];
        yield 'error' => [
            new Request(request: ['type' => 'error', 'error' => 'Something happened']),
            Level::Warning,
            LogCode::STOREFRONT_ERROR_REPORTED,
            'The browser reported a PayPal error',
        ];
    }

    /**
     * @param array<string, string> $headers
     */
    #[DataProvider('dataProviderClearVaultRequests')]
    public function testClearVaultOnlyClearsGetRequestsFromTheShop(string $method, array $headers, bool $expectCleared): void
    {
        $request = Request::create('https://example.test/paypal/vault/clear', $method);
        $request->headers->add($headers);

        $this->clearVaultRoute
            ->expects($expectCleared ? static::once() : static::never())
            ->method('clearVault');

        $this->controller->clearVault($request, $this->generateSalesChannelContext());
    }

    public static function dataProviderClearVaultRequests(): \Generator
    {
        yield 'POST' => [Request::METHOD_POST, [], true];
        yield 'GET from the shop' => [Request::METHOD_GET, ['Sec-Fetch-Site' => 'same-origin'], true];
        yield 'GET from another website' => [Request::METHOD_GET, ['Sec-Fetch-Site' => 'cross-site', 'Referer' => 'https://example.test/'], false];
        yield 'GET from another subdomain' => [Request::METHOD_GET, ['Sec-Fetch-Site' => 'same-site'], false];
        yield 'GET from the address bar' => [Request::METHOD_GET, ['Sec-Fetch-Site' => 'none'], false];
        yield 'HEAD from another website' => [Request::METHOD_HEAD, ['Sec-Fetch-Site' => 'cross-site'], false];
        yield 'GET without fetch metadata from the shop' => [Request::METHOD_GET, ['Referer' => 'https://example.test/checkout/confirm'], true];
        yield 'GET without fetch metadata from another website' => [Request::METHOD_GET, ['Referer' => 'https://example.test.attacker.test/'], false];
        yield 'GET without fetch metadata and referer' => [Request::METHOD_GET, [], false];
    }

    private function generateSalesChannelContext(): SalesChannelContext
    {
        $paymentMethod = (new PaymentMethodEntity())->assign([
            'id' => 'test',
            'name' => 'Generated Payment',
            'technicalName' => 'swag_paypal_test',
            'active' => true,
            'formattedHandlerIdentifier' => 'test_handler',
        ]);

        return Generator::createSalesChannelContext(paymentMethod: $paymentMethod);
    }
}
