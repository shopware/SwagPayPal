<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Util;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Util\Availability\AvailabilityContext;
use Swag\PayPal\Util\PayLaterAvailabilityChecker;

/**
 * @internal
 */
#[Package('checkout'), CoversClass(PayLaterAvailabilityChecker::class)]
class PayLaterAvailabilityCheckerTest extends TestCase
{
    #[DataProvider('availabilityProvider')]
    public function testIsPayLaterAvailableWithValidData(string $currencyCode, string $countryCode, float $amount, bool $expected): void
    {
        $availabilityContext = new AvailabilityContext();
        $availabilityContext->assign([
            'billingCountryCode' => $countryCode,
            'currencyCode' => $currencyCode,
            'totalAmount' => $amount,
        ]);

        $result = PayLaterAvailabilityChecker::isPayLaterAvailable($availabilityContext);

        static::assertSame($expected, $result);
    }

    /**
     * @return array<array{string, string, float, bool}>
     */
    public static function availabilityProvider(): array
    {
        return [
            ['ZAR', 'ZA', 1000.00, false],
            ['EUR', 'DE', 50.00, true],
            ['EUR', 'DE', 0.50, false],
            ['EUR', 'DE', 6000.00, false],
            ['USD', 'US', 100.00, true],
            ['USD', 'US', 160000.00, false],
            ['GBP', 'GB', 50.00, true],
            ['GBP', 'GB', 20.00, false],
            ['AUD', 'AU', 100.00, true],
            ['AUD', 'AU', 2500.00, false],
            ['EUR', 'FR', 50.00, true],
            ['EUR', 'FR', 20.00, false],
            ['EUR', 'IT', 50.00, true],
            ['EUR', 'IT', 20.00, false],
            ['EUR', 'ES', 50.00, true],
            ['EUR', 'ES', 20.00, false],
        ];
    }
}
