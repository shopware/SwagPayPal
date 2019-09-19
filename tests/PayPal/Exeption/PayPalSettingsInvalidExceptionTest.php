<?php declare(strict_types=1);

namespace Swag\PayPal\Test\PayPal\Exeption;

use PHPUnit\Framework\TestCase;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Symfony\Component\HttpFoundation\Response;

class PayPalSettingsInvalidExceptionTest extends TestCase
{
    public function testGetStatusCode(): void
    {
        $settingOption = 'intent';
        $exception = new PayPalSettingsInvalidException($settingOption);

        static::assertSame(sprintf('Required setting "%s" is missing or invalid', $settingOption), $exception->getMessage());
        static::assertSame(Response::HTTP_NOT_FOUND, $exception->getStatusCode());
    }
}
