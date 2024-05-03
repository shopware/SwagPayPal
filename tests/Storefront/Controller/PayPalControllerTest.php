<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Storefront\Controller;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\SalesChannel\AbstractCartDeleteRoute;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestSessionStorage;
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

/**
 * @internal
 */
#[CoversClass(PayPalController::class)]
#[Package('checkout')]
class PayPalControllerTest extends TestCase
{
    private AbstractCreateOrderRoute&MockObject $createOrderRoute;

    private PayPalController&MockObject $controller;

    protected function setUp(): void
    {
        $this->createOrderRoute = $this->createMock(AbstractCreateOrderRoute::class);

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
                $this->createMock(LoggerInterface::class),
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

    public function testOnHandleErrorWithTranslatableErrorCode(): void
    {
        $request = new Request(request: ['code' => 'SWAG_PAYPAL__TRANSLATABLE_ERROR_CODE']);

        $matcher = static::exactly(2);
        $this->controller
            ->expects($matcher)
            ->method('trans')
            ->willReturnCallback(function (string $key) use (&$matcher) {
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

        $paymentMethod = (new PaymentMethodEntity())->assign([
            'id' => 'test',
            'formattedHandlerIdentifier' => 'test_handler',
        ]);

        $this->controller->onHandleError($request, Generator::createSalesChannelContext(paymentMethod: $paymentMethod));
    }

    public function testOnHandleErrorWithNonTranslatableErrorCode(): void
    {
        $request = new Request(request: ['code' => 'SWAG_PAYPAL__NON_TRANSLATABLE_ERROR_CODE']);

        $this->controller
            ->expects(static::exactly(3))
            ->method('trans')
            ->willReturnCallback(fn (string $key) => $key);

        $this->controller
            ->expects(static::once())
            ->method('addFlash')
            ->with('danger', 'paypal.error.SWAG_PAYPAL__GENERIC_ERROR');

        $this->controller->onHandleError($request, $this->generateSalesChannelContext());
    }

    public function testOnHandleErrorWithNonFatalError(): void
    {
        $request = $this->getMockBuilder(Request::class)
            ->onlyMethods(['getSession'])
            ->setConstructorArgs([
                'request' => ['code' => 'SWAG_PAYPAL__TRANSLATABLE_ERROR_CODE'],
            ])
            ->getMock();

        $request->expects(static::never())->method('getSession');

        $this->controller->onHandleError($request, $this->generateSalesChannelContext());
    }

    public function testOnHandleErrorWithFatalError(): void
    {
        $request = $this->getMockBuilder(Request::class)
            ->onlyMethods(['getSession'])
            ->setConstructorArgs([
                'request' => [
                    'code' => 'SWAG_PAYPAL__TRANSLATABLE_ERROR_CODE',
                    'fatal' => true,
                ],
            ])
            ->getMock();

        $session = new Session(new TestSessionStorage());

        $request
            ->expects(static::once())
            ->method('getSession')
            ->willReturn($session);

        $this->controller->onHandleError($request, $this->generateSalesChannelContext());

        static::assertSame(
            $session->get(PayPalController::PAYMENT_METHOD_FATAL_ERROR),
            $this->generateSalesChannelContext()->getPaymentMethod()->getId()
        );
    }

    private function generateSalesChannelContext(): SalesChannelContext
    {
        $paymentMethod = (new PaymentMethodEntity())->assign([
            'id' => 'test',
            'name' => 'Generated Payment',
            'active' => true,
            'formattedHandlerIdentifier' => 'test_handler',
        ]);

        return Generator::createSalesChannelContext(paymentMethod: $paymentMethod);
    }
}
