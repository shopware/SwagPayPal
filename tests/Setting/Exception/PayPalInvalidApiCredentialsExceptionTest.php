<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Setting\Exception;

use PHPUnit\Framework\TestCase;
use Swag\PayPal\Setting\Exception\PayPalInvalidApiCredentialsException;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
class PayPalInvalidApiCredentialsExceptionTest extends TestCase
{
    public function testGetStatusCode(): void
    {
        $exception = new PayPalInvalidApiCredentialsException();

        static::assertSame('Provided API credentials are invalid', $exception->getMessage());
        static::assertSame(Response::HTTP_UNAUTHORIZED, $exception->getStatusCode());
    }
}
