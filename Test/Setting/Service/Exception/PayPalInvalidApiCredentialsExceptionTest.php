<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Test\Setting\Service\Exception;

use PHPUnit\Framework\TestCase;
use SwagPayPal\Setting\Exception\PayPalInvalidApiCredentialsException;
use Symfony\Component\HttpFoundation\Response;

class PayPalInvalidApiCredentialsExceptionTest extends TestCase
{
    public function testGetStatusCode()
    {
        $exception = new PayPalInvalidApiCredentialsException();

        self::assertSame('Provided API credentials are invalid', $exception->getMessage());
        self::assertSame(Response::HTTP_NOT_FOUND, $exception->getStatusCode());
    }
}
