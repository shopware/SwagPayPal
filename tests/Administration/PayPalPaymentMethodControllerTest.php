<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Administration;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Routing\RoutingException;
use Swag\PayPal\Administration\PayPalPaymentMethodController;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Swag\PayPal\Test\Mock\Repositories\PaymentMethodRepoMock;
use Swag\PayPal\Test\Mock\Repositories\SalesChannelRepoMock;
use Swag\PayPal\Test\Util\PaymentMethodUtilTest;
use Swag\PayPal\Util\PaymentMethodUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
#[Package('checkout')]
class PayPalPaymentMethodControllerTest extends TestCase
{
    public function testSetPayPalPaymentMethodAsSalesChannelDefault(): void
    {
        $salesChannelRepoMock = new SalesChannelRepoMock();

        $connection = $this->createMock(Connection::class);
        $connection->expects(static::once())
            ->method('fetchAllKeyValue')
            ->willReturn([PayPalPaymentHandler::class => PaymentMethodRepoMock::PAYPAL_PAYMENT_METHOD_ID]);
        $paymentMethodUtil = new PaymentMethodUtil($connection, $salesChannelRepoMock);
        $context = Context::createDefaultContext();

        $response = $this->createPayPalPaymentMethodController($salesChannelRepoMock, $paymentMethodUtil)
            ->setPayPalPaymentMethodAsSalesChannelDefault(new Request(), $context);
        static::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());

        $updates = $salesChannelRepoMock->getUpdateData();
        static::assertCount(1, $updates);
        $updateData = $updates[0];
        static::assertArrayHasKey('id', $updateData);
        static::assertSame(PaymentMethodUtilTest::SALESCHANNEL_WITHOUT_PAYPAL_PAYMENT_METHOD, $updateData['id']);
        static::assertArrayHasKey('paymentMethodId', $updateData);
        $payPalPaymentMethodId = $paymentMethodUtil->getPayPalPaymentMethodId($context);
        static::assertNotNull($payPalPaymentMethodId);
        static::assertSame($payPalPaymentMethodId, $updateData['paymentMethodId']);
    }

    public function testSetPayPalPaymentMethodInvalidParameter(): void
    {
        $request = new Request([], ['salesChannelId' => true]);
        $context = Context::createDefaultContext();

        $this->expectException(RoutingException::class);
        $this->expectExceptionMessage('The parameter "salesChannelId" is invalid.');
        $this->createPayPalPaymentMethodController()->setPayPalPaymentMethodAsSalesChannelDefault($request, $context);
    }

    private function createPayPalPaymentMethodController(
        ?SalesChannelRepoMock $salesChannelRepoMock = null,
        ?PaymentMethodUtil $paymentMethodUtil = null
    ): PayPalPaymentMethodController {
        if ($salesChannelRepoMock === null) {
            $salesChannelRepoMock = new SalesChannelRepoMock();
        }
        if ($paymentMethodUtil === null) {
            $paymentMethodUtil = new PaymentMethodUtil($this->createMock(Connection::class), $salesChannelRepoMock);
        }

        return new PayPalPaymentMethodController($paymentMethodUtil);
    }
}
