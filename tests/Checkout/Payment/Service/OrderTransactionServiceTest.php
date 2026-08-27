<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\Payment\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Swag\PayPal\Checkout\CheckoutException;
use Swag\PayPal\Checkout\Payment\Service\OrderTransactionService;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(OrderTransactionService::class)]
class OrderTransactionServiceTest extends TestCase
{
    private Connection $connection;

    private OrderTransactionService $service;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->service = new OrderTransactionService($this->connection);
    }

    public function testReservesNewPaypalOrderIdWithOneQuery(): void
    {
        $this->connection
            ->expects($this->once())
            ->method('insert');
        $this->connection->expects($this->never())->method('fetchOne');

        $this->service->reserve('paypal-order-id', Uuid::randomHex());
    }

    public function testAllowsSamePaypalOrderIdForSameTransaction(): void
    {
        $this->connection
            ->expects($this->once())
            ->method('insert')
            ->willThrowException($this->createMock(UniqueConstraintViolationException::class));
        $this->connection
            ->expects($this->once())
            ->method('fetchOne')
            ->willReturn(false);

        $this->service->reserve('paypal-order-id', Uuid::randomHex());
    }

    public function testRejectsPaypalOrderIdForDifferentTransaction(): void
    {
        $this->connection
            ->expects($this->once())
            ->method('insert')
            ->willThrowException($this->createMock(UniqueConstraintViolationException::class));
        $this->connection
            ->expects($this->once())
            ->method('fetchOne')
            ->willReturn(1);

        $this->expectExceptionObject(CheckoutException::paypalOrderAlreadyUsed());

        $this->service->reserve('paypal-order-id', Uuid::randomHex());
    }
}
