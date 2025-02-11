<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Util;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Util\PayLaterAvailabilityChecker;

/**
 * @internal
 */
#[Package('checkout'), CoversClass(PayLaterAvailabilityChecker::class)]
class PayLaterAvailabilityCheckerTest extends TestCase
{
    public function testIsPayLaterAvailableWithValidData(): void
    {
        $countryCode = 'US';
        $currencyCode = 'USD';
        $totalAmount = 100.00;

        $result = PayLaterAvailabilityChecker::isPayLaterAvailable($countryCode, $currencyCode, $totalAmount);

        static::assertTrue($result);
    }

    public function testIsPayLaterAvailableWithInvalidCountry(): void
    {
        $countryCode = 'XX';
        $currencyCode = 'USD';
        $totalAmount = 100.00;

        $result = PayLaterAvailabilityChecker::isPayLaterAvailable($countryCode, $currencyCode, $totalAmount);

        static::assertFalse($result);
    }

    public function testIsPayLaterAvailableWithInvalidCurrency(): void
    {
        $countryCode = 'US';
        $currencyCode = 'XXX';
        $totalAmount = 100.00;

        $result = PayLaterAvailabilityChecker::isPayLaterAvailable($countryCode, $currencyCode, $totalAmount);

        static::assertFalse($result);
    }

    public function testIsPayLaterAvailableWithLowAmount(): void
    {
        $countryCode = 'US';
        $currencyCode = 'USD';
        $totalAmount = 1.00;

        $result = PayLaterAvailabilityChecker::isPayLaterAvailable($countryCode, $currencyCode, $totalAmount);

        static::assertFalse($result);
    }
}
