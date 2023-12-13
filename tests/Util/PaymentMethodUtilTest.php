<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Util;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\Test\Generator;
use Shopware\Core\Test\TestDefaults;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Swag\PayPal\Test\Mock\Repositories\PaymentMethodRepoMock;
use Swag\PayPal\Test\Mock\Repositories\SalesChannelRepoMock;
use Swag\PayPal\Util\PaymentMethodUtil;

/**
 * @internal
 */
#[Package('checkout')]
class PaymentMethodUtilTest extends TestCase
{
    public const SALESCHANNEL_WITHOUT_PAYPAL_PAYMENT_METHOD = '4ce46b49d1904a5db0b41573e9355b51';

    private PaymentMethodUtil $paymentMethodUtil;

    private SalesChannelRepoMock $salesChannelRepoMock;

    /**
     * @var Connection&MockObject
     */
    private Connection $connectionMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->salesChannelRepoMock = new SalesChannelRepoMock();
        $this->connectionMock = $this->createMock(Connection::class);
        $this->paymentMethodUtil = new PaymentMethodUtil(
            $this->connectionMock,
            $this->salesChannelRepoMock
        );
    }

    public function testGetPayPalPaymentMethodId(): void
    {
        $this->connectionMock->expects(static::once())
            ->method('fetchAllKeyValue')
            ->willReturn([PayPalPaymentHandler::class => PaymentMethodRepoMock::PAYPAL_PAYMENT_METHOD_ID]);

        $paymentMethodId = $this->paymentMethodUtil->getPayPalPaymentMethodId(Context::createDefaultContext());

        static::assertSame(PaymentMethodRepoMock::PAYPAL_PAYMENT_METHOD_ID, $paymentMethodId);
    }

    public function testGetPayPalPaymentMethodIdWithWrongHandler(): void
    {
        $this->connectionMock->expects(static::once())
            ->method('fetchAllKeyValue')
            ->willReturn([]);

        $paymentMethodId = $this->paymentMethodUtil->getPayPalPaymentMethodId(Context::createDefaultContext());

        static::assertNull($paymentMethodId);
    }

    public function testGetPaypalPaymentMethodInSalesChannel(): void
    {
        $this->connectionMock->expects(static::once())
            ->method('fetchAllKeyValue')
            ->willReturn([PayPalPaymentHandler::class => PaymentMethodRepoMock::PAYPAL_PAYMENT_METHOD_ID]);

        $this->connectionMock->expects(static::once())
            ->method('fetchFirstColumn')
            ->willReturn([TestDefaults::SALES_CHANNEL]);

        $salesChannel = new SalesChannelEntity();
        $salesChannel->setId(TestDefaults::SALES_CHANNEL);
        $salesChannelContext = Generator::createSalesChannelContext(
            null,
            null,
            $salesChannel
        );
        static::assertTrue($this->paymentMethodUtil->isPaypalPaymentMethodInSalesChannel($salesChannelContext));
    }

    public function testGetPaypalPaymentMethodInSalesChannelWithoutPayPalPaymentMethodId(): void
    {
        $this->connectionMock->expects(static::once())
            ->method('fetchAllKeyValue')
            ->willReturn([]);

        $this->connectionMock->expects(static::never())->method('fetchFirstColumn');

        $salesChannel = new SalesChannelEntity();
        $salesChannel->setId(TestDefaults::SALES_CHANNEL);
        $salesChannelContext = Generator::createSalesChannelContext(
            null,
            null,
            $salesChannel
        );
        static::assertFalse($this->paymentMethodUtil->isPaypalPaymentMethodInSalesChannel($salesChannelContext));
    }

    public function testGetPaypalPaymentMethodInSalesChannelWithoutAssignment(): void
    {
        $this->connectionMock->expects(static::once())
            ->method('fetchAllKeyValue')
            ->willReturn([PayPalPaymentHandler::class => PaymentMethodRepoMock::PAYPAL_PAYMENT_METHOD_ID]);

        $this->connectionMock->expects(static::once())
            ->method('fetchFirstColumn')
            ->willReturn([]);

        $salesChannel = new SalesChannelEntity();
        $salesChannel->setId(TestDefaults::SALES_CHANNEL);
        $salesChannelContext = Generator::createSalesChannelContext(
            null,
            null,
            $salesChannel
        );
        static::assertFalse($this->paymentMethodUtil->isPaypalPaymentMethodInSalesChannel($salesChannelContext));
    }

    public function testSetPayPalAsDefaultPaymentMethodForASpecificSalesChannel(): void
    {
        $this->connectionMock->expects(static::once())
            ->method('fetchAllKeyValue')
            ->willReturn([PayPalPaymentHandler::class => PaymentMethodRepoMock::PAYPAL_PAYMENT_METHOD_ID]);

        $context = Context::createDefaultContext();
        $this->paymentMethodUtil->setPayPalAsDefaultPaymentMethod($context, TestDefaults::SALES_CHANNEL);
        $this->assertPaymentMethodUpdate($context);
    }

    public function testSetPayPalAsDefaultPaymentMethodForAllCompatibleSalesChannels(): void
    {
        $this->connectionMock->expects(static::once())
            ->method('fetchAllKeyValue')
            ->willReturn([PayPalPaymentHandler::class => PaymentMethodRepoMock::PAYPAL_PAYMENT_METHOD_ID]);

        $context = Context::createDefaultContext();
        $this->paymentMethodUtil->setPayPalAsDefaultPaymentMethod($context, null);
        $this->assertPaymentMethodUpdate($context, false);
    }

    public function testSetPayPalAsDefaultPaymentWithoutBeingPresentForTheRequestedSalesChannel(): void
    {
        $this->connectionMock->expects(static::once())
            ->method('fetchAllKeyValue')
            ->willReturn([PayPalPaymentHandler::class => PaymentMethodRepoMock::PAYPAL_PAYMENT_METHOD_ID]);

        $context = Context::createDefaultContext();
        $this->paymentMethodUtil->setPayPalAsDefaultPaymentMethod($context, self::SALESCHANNEL_WITHOUT_PAYPAL_PAYMENT_METHOD);
        $this->assertPaymentMethodUpdate($context, false);
    }

    private function assertPaymentMethodUpdate(Context $context, bool $paypalPaymentMethodPresent = true): void
    {
        $updates = $this->salesChannelRepoMock->getUpdateData();
        static::assertCount(1, $updates);
        $updateData = $updates[0];
        static::assertCount($paypalPaymentMethodPresent ? 2 : 3, $updateData);
        static::assertArrayHasKey('id', $updateData);
        static::assertSame($paypalPaymentMethodPresent ? TestDefaults::SALES_CHANNEL : self::SALESCHANNEL_WITHOUT_PAYPAL_PAYMENT_METHOD, $updateData['id']);
        static::assertArrayHasKey('paymentMethodId', $updateData);
        $payPalPaymentMethodId = $this->paymentMethodUtil->getPayPalPaymentMethodId($context);
        static::assertNotNull($payPalPaymentMethodId);
        static::assertSame($payPalPaymentMethodId, $updateData['paymentMethodId']);
    }
}
