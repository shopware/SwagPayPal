<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Tests\AgentCommerce\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\AgentCommerce\Exception\AgentException;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(AgentException::class)]
class AgentExceptionTest extends TestCase
{
    public function testRequiredFieldsMissing(): void
    {
        $exception = AgentException::requiredFieldsMissing('field1', 'field2');

        static::assertSame(400, $exception->getStatusCode());
        static::assertSame(AgentException::INVALID_REQUEST, $exception->getErrorCode());
        static::assertSame('Required field \'field1, field2\' is missing', $exception->getMessage());

        $details = $exception->getDetails();

        static::assertCount(2, $details);

        static::assertSame('field1', $details->first()?->getField());
        static::assertSame('MISSING_REQUIRED_FIELD', $details->first()->getIssue());
        static::assertSame('The field \'field1\' is required and cannot be empty', $details->first()->getDescription());

        static::assertSame('field2', $details->last()?->getField());
        static::assertSame('MISSING_REQUIRED_FIELD', $details->last()->getIssue());
        static::assertSame('The field \'field2\' is required and cannot be empty', $details->last()->getDescription());
    }

    public function testInvalidJSONFormat(): void
    {
        $exception = AgentException::invalidJSONFormat();

        static::assertSame(400, $exception->getStatusCode());
        static::assertSame(AgentException::INVALID_REQUEST, $exception->getErrorCode());
        static::assertSame('Request body contains invalid JSON', $exception->getMessage());
    }

    public function testUnauthorized(): void
    {
        $exception = AgentException::unauthorized('Invalid JWT token');

        static::assertSame(401, $exception->getStatusCode());
        static::assertSame(AgentException::INVALID_REQUEST, $exception->getErrorCode());
        static::assertSame('Invalid JWT token', $exception->getMessage());
    }

    public function testCartNotFound(): void
    {
        $exception = AgentException::cartNotFound('123');

        static::assertSame(404, $exception->getStatusCode());
        static::assertSame(AgentException::CART_NOT_FOUND, $exception->getErrorCode());
        static::assertSame('Cart with ID \'123\' does not exist', $exception->getMessage());
    }

    public function testDatabaseConnectionFailure(): void
    {
        $exception = AgentException::databaseConnectionFailure();

        static::assertSame(500, $exception->getStatusCode());
        static::assertSame(AgentException::INTERNAL_SERVER_ERROR, $exception->getErrorCode());
        static::assertSame('A temporary system error occurred. Please try again later.', $exception->getMessage());
    }

    public function testExternalServiceFailure(): void
    {
        $exception = AgentException::externalServiceFailure();

        static::assertSame(500, $exception->getStatusCode());
        static::assertSame(AgentException::SERVICE_UNAVAILABLE, $exception->getErrorCode());
        static::assertSame('The payment processor is currently unavailable. Please try again later.', $exception->getMessage());
    }

    public function testPaymentProcessorUnavailable(): void
    {
        $exception = AgentException::paymentProcessorUnavailable();

        static::assertSame(500, $exception->getStatusCode());
        static::assertSame(AgentException::PAYMENT_PROCESSOR_UNAVAILABLE, $exception->getErrorCode());
        static::assertSame('Payment processing is temporarily unavailable', $exception->getMessage());
    }

    public function testPaymentCaptureFailed(): void
    {
        $exception = AgentException::paymentCaptureFailed('Insufficient funds');

        static::assertSame(500, $exception->getStatusCode());
        static::assertSame(AgentException::PAYMENT_CAPTURE_FAILED, $exception->getErrorCode());
        static::assertSame('Unable to capture payment at this time', $exception->getMessage());

        $details = $exception->getDetails();

        static::assertCount(1, $details);

        static::assertSame('payment_method', $details->first()?->getField());
        static::assertSame('CAPTURE_FAILED', $details->first()->getIssue());
        static::assertSame('Insufficient funds', $details->first()->getDescription());
    }

    public function inventorySystemError(): void
    {
        $exception = AgentException::inventorySystemError();

        static::assertSame(500, $exception->getStatusCode());
        static::assertSame(AgentException::INTERNAL_SERVER_ERROR, $exception->getErrorCode());
        static::assertSame('Unable to reserve inventory for checkout', $exception->getMessage());
    }

    public function testOrderSystemError(): void
    {
        $exception = AgentException::orderSystemError();

        static::assertSame(500, $exception->getStatusCode());
        static::assertSame(AgentException::ORDER_SYSTEM_ERROR, $exception->getErrorCode());
        static::assertSame('Order could not be created due to system error', $exception->getMessage());
    }
}
