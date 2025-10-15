<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Storefront\Controller;

use Monolog\Handler\TestHandler;
use Monolog\Level;
use Monolog\Logger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\SalesChannel\AbstractCartDeleteRoute;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannel\AbstractContextSwitchRoute;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Test\Generator;
use Swag\PayPal\Checkout\ExpressCheckout\SalesChannel\AbstractExpressCreateOrderRoute;
use Swag\PayPal\Checkout\ExpressCheckout\SalesChannel\AbstractExpressPrepareCheckoutRoute;
use Swag\PayPal\Checkout\PUI\SalesChannel\AbstractPUIPaymentInstructionsRoute;
use Swag\PayPal\Checkout\SalesChannel\AbstractClearVaultRoute;
use Swag\PayPal\Checkout\SalesChannel\AbstractCreateOrderRoute;
use Swag\PayPal\Checkout\SalesChannel\AbstractMethodEligibilityRoute;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\Storefront\Controller\PayPalController;
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
    private AbstractCreateOrderRoute&MockObject $createOrderRoute;

    private TestHandler $logHandler;

    private PayPalController&MockObject $controller;

    protected function setUp(): void
    {
        $this->createOrderRoute = $this->createMock(AbstractCreateOrderRoute::class);
        $this->logHandler = new TestHandler();

        $this->controller = $this->getMockBuilder(PayPalController::class)
            ->onlyMethods(['trans', 'addFlash'])
            ->setConstructorArgs([
                $this->createOrderRoute,
                $this->createMock(AbstractMethodEligibilityRoute::class),
                $this->createMock(AbstractPUIPaymentInstructionsRoute::class),
                $this->createMock(AbstractExpressPrepareCheckoutRoute::class),
                $this->createMock(AbstractExpressCreateOrderRoute::class),
                $this->createMock(AbstractContextSwitchRoute::class),
                $this->createMock(AbstractCartDeleteRoute::class),
                $this->createMock(AbstractClearVaultRoute::class),
                new Logger('test', [$this->logHandler]),
            ])
            ->getMock();
    }

    public function testCreateOrderWillReturnErrorResponseOnThrownPayPalApiException(): void
    {
        $exception = new PayPalApiException('test', 'message', issue: 'issue');

        $this->createOrderRoute
            ->expects($this->once())
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

    public function testOnHandleErrorWithTranslatableErrorCode(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $request = new Request([], ['code' => 'SWAG_PAYPAL__TRANSLATABLE_ERROR_CODE']);
        $request->setSession($session);

        $context = $this->generateSalesChannelContext();

        $this->controller->expects($this->exactly(2))
            ->method('trans')
            ->willReturnCallback(function (string $key) {
                if ($key === 'paypal.error.test_handler.SWAG_PAYPAL__TRANSLATABLE_ERROR_CODE') {
                    return 'Translated error message';
                }

                return $key;
            });

        $this->controller->onHandleError($request, $context);

        $errors = $session->get('paypal_page_errors', []);
        static::assertNotEmpty($errors);
        static::assertSame(['Translated error message'], $errors);

        $this->assertLogRecord(Level::Warning, [
            'code' => 'SWAG_PAYPAL__TRANSLATABLE_ERROR_CODE',
            'fatal' => false,
        ]);
    }

    public function testOnHandleErrorWithNonTranslatableErrorCode(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $request = new Request([], ['code' => 'SWAG_PAYPAL__NON_TRANSLATABLE_ERROR_CODE']);
        $request->setSession($session);

        $context = $this->generateSalesChannelContext();

        $this->controller->expects($this->exactly(3))
            ->method('trans')
            ->willReturnCallback(fn (string $key) => $key);

        $this->controller->onHandleError($request, $context);

        $errors = $session->get('paypal_page_errors', []);
        static::assertNotEmpty($errors);
        static::assertSame(
            ['paypal.error.SWAG_PAYPAL__GENERIC_ERROR'],
            $errors
        );

        $this->assertLogRecord(Level::Warning, [
            'code' => 'SWAG_PAYPAL__NON_TRANSLATABLE_ERROR_CODE',
            'fatal' => false,
        ]);
    }

    #[DataProvider('onHandleErrorDataProvider')]
    public function testOnHandleError(string $code, bool $fatal, Level $level): void
    {
        $salesChannelContext = $this->generateSalesChannelContext();
        $request = new Request(request: [
            'code' => $code,
            'fatal' => $fatal,
        ]);

        $session = new Session(new MockArraySessionStorage());
        $request->setSession($session);

        $this->controller->onHandleError($request, $salesChannelContext);

        static::assertSame(
            $fatal ? $salesChannelContext->getPaymentMethod()->getId() : null,
            $session->get(PayPalController::PAYMENT_METHOD_FATAL_ERROR),
        );

        $this->assertLogRecord($level, [
            'code' => $code,
            'fatal' => $fatal,
        ]);
    }

    public static function onHandleErrorDataProvider(): \Generator
    {
        yield 'fatal script error' => ['SWAG_PAYPAL__SCRIPT_ERROR', true, Level::Error];
        yield 'fatal script not loaded' => ['SWAG_PAYPAL__SCRIPT_NOT_LOADED', true, Level::Error];
        yield 'fatal generic error' => ['SWAG_PAYPAL__GENERIC_ERROR', true, Level::Warning];
        yield 'fatal eligible error' => ['SWAG_PAYPAL__NOT_ELIGIBLE', true, Level::Warning];

        yield 'script error' => ['SWAG_PAYPAL__SCRIPT_ERROR', false, Level::Error];
        yield 'script not loaded' => ['SWAG_PAYPAL__SCRIPT_NOT_LOADED', false, Level::Error];
        yield 'generic error' => ['SWAG_PAYPAL__GENERIC_ERROR', false, Level::Warning];
        yield 'eligible error' => ['SWAG_PAYPAL__NOT_ELIGIBLE', false, Level::Warning];
    }

    private function generateSalesChannelContext(): SalesChannelContext
    {
        $paymentMethod = (new PaymentMethodEntity())->assign([
            'id' => 'test',
            'name' => 'Generated Payment',
            'active' => true,
            'formattedHandlerIdentifier' => 'test_handler',
        ]);

        return Generator::generateSalesChannelContext(paymentMethod: $paymentMethod);
    }

    private function assertLogRecord(Level $level, array $context): void
    {
        $records = $this->logHandler->getRecords();
        static::assertCount(1, $records);
        static::assertSame($level, $records[0]->level);
        static::assertEquals([
            'paymentMethodId' => 'test',
            'paymentMethodName' => 'Generated Payment',
            'error' => null,
            ...$context,
        ], $records[0]->context);
    }
}
