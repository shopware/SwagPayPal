<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Setting\Exception;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
#[Package('checkout')]
class PayPalSettingsInvalidExceptionTest extends TestCase
{
    public function testGetStatusCode(): void
    {
        $settingOption = 'intent';
        $exception = new PayPalSettingsInvalidException($settingOption);

        static::assertSame(\sprintf('Required setting "%s" is missing or invalid', $settingOption), $exception->getMessage());
        static::assertSame(Response::HTTP_NOT_FOUND, $exception->getStatusCode());
    }
}
