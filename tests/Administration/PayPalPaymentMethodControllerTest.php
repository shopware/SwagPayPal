<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Administration;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Routing\RoutingException;
use Swag\PayPal\Administration\PayPalPaymentMethodController;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Swag\PayPal\Test\Mock\Repositories\PaymentMethodRepoMock;
use Swag\PayPal\Test\Mock\Repositories\SalesChannelRepoMock;
use Swag\PayPal\Test\Util\PaymentMethodUtilTest;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;
use Swag\PayPal\Util\PaymentMethodUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
#[Package('checkout')]
class PayPalPaymentMethodControllerTest extends TestCase
{
    private SalesChannelRepoMock $salesChannelRepo;

    private MockObject&Connection $connection;

    private PayPalPaymentMethodController $payPalPaymentMethodController;

    protected function setUp(): void
    {
        $this->payPalPaymentMethodController = new PayPalPaymentMethodController(
            new PaymentMethodUtil(
                $this->connection = $this->createMock(Connection::class),
                $this->salesChannelRepo = new SalesChannelRepoMock(),
                $this->createMock(PaymentMethodDataRegistry::class),
            ),
        );
    }

    public function testSetPayPalPaymentMethodAsSalesChannelDefault(): void
    {
        $this->connection->expects($this->once())
            ->method('fetchAllKeyValue')
            ->willReturn([PayPalPaymentHandler::class => PaymentMethodRepoMock::PAYPAL_PAYMENT_METHOD_ID]);

        $context = Context::createDefaultContext();

        $response = $this->payPalPaymentMethodController->setPayPalPaymentMethodAsSalesChannelDefault(new Request(), $context);
        static::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());

        $updates = $this->salesChannelRepo->getUpdateData();
        static::assertCount(1, $updates);
        $updateData = $updates[0];
        static::assertArrayHasKey('id', $updateData);
        static::assertSame(PaymentMethodUtilTest::SALESCHANNEL_WITHOUT_PAYPAL_PAYMENT_METHOD, $updateData['id']);
        static::assertArrayHasKey('paymentMethodId', $updateData);
        static::assertSame(PaymentMethodRepoMock::PAYPAL_PAYMENT_METHOD_ID, $updateData['paymentMethodId']);
    }

    public function testSetPayPalPaymentMethodInvalidParameter(): void
    {
        $request = new Request([], ['salesChannelId' => true]);
        $context = Context::createDefaultContext();

        $this->expectException(RoutingException::class);
        $this->expectExceptionMessageMatches('/\A' . \preg_quote('The parameter "salesChannelId" is invalid.', '/') . '\z/');
        $this->payPalPaymentMethodController->setPayPalPaymentMethodAsSalesChannelDefault($request, $context);
    }
}
