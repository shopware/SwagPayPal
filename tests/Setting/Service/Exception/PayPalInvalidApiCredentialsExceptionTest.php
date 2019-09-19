<?php declare(strict_types=1);

namespace Swag\PayPal\Test\Setting\Service\Exception;

use PHPUnit\Framework\TestCase;
use Swag\PayPal\Setting\Exception\PayPalInvalidApiCredentialsException;
use Symfony\Component\HttpFoundation\Response;

class PayPalInvalidApiCredentialsExceptionTest extends TestCase
{
    public function testGetStatusCode(): void
    {
        $exception = new PayPalInvalidApiCredentialsException();

        static::assertSame('Provided API credentials are invalid', $exception->getMessage());
        static::assertSame(Response::HTTP_NOT_FOUND, $exception->getStatusCode());
    }
}
