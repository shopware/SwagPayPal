<?php declare(strict_types=1);

namespace Swag\PayPal\Test\Checkout\Payment\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Swag\PayPal\Checkout\CheckoutException;
use Swag\PayPal\Checkout\Payment\Service\OrderTransactionService;

#[Package('checkout')]
#[CoversClass(OrderTransactionService::class)]
class OrderTransactionServiceTest extends TestCase
{
    public function testNewOrderIdIsReserved(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(static::once())->method('insert');

        (new OrderTransactionService($connection))->reserve('paypal-order-id', Uuid::randomHex());
    }

    public function testSameTransactionCanReserveIdempotently(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(static::once())->method('insert')->willThrowException($this->createMock(UniqueConstraintViolationException::class));
        $connection->expects(static::once())->method('fetchOne')->willReturn(false);

        (new OrderTransactionService($connection))->reserve('paypal-order-id', Uuid::randomHex());
    }

    public function testDifferentTransactionCannotReserveExistingOrderId(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(static::once())->method('insert')->willThrowException($this->createMock(UniqueConstraintViolationException::class));
        $connection->expects(static::once())->method('fetchOne')->willReturn(1);

        $this->expectExceptionObject(CheckoutException::paypalOrderAlreadyUsed());
        (new OrderTransactionService($connection))->reserve('paypal-order-id', Uuid::randomHex());
    }
}
