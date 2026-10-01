<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Util\Lifecycle\Method;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Util\Availability\AvailabilityContext;
use Swag\PayPal\Util\Lifecycle\Method\P24MethodData;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @internal
 */
#[Package('checkout')]
class P24MethodDataTest extends TestCase
{
    private P24MethodData $p24MethodData;

    protected function setUp(): void
    {
        $this->p24MethodData = new P24MethodData($this->createMock(ContainerInterface::class));
    }

    /**
     * @dataProvider availabilityProvider
     */
    public function testIsAvailable(string $currencyCode, string $countryCode, float $totalAmount, bool $expected): void
    {
        $availabilityContext = new AvailabilityContext();
        $availabilityContext->assign([
            'currencyCode' => $currencyCode,
            'billingCountryCode' => $countryCode,
            'totalAmount' => $totalAmount,
        ]);

        static::assertSame($expected, $this->p24MethodData->isAvailable($availabilityContext));
    }

    /**
     * @return array<array{string, string, float, bool}>
     */
    public static function availabilityProvider(): array
    {
        return [
            ['PLN', 'PL', 1.00, true],
            ['PLN', 'PL', 55000.00, true],
            ['PLN', 'PL', 0.99, false],
            ['PLN', 'PL', 55000.01, false],
            ['EUR', 'PL', 1.00, true],
            ['EUR', 'PL', 60000.00, true],
            ['EUR', 'PL', 0.99, false],
            ['USD', 'PL', 100.00, false],
            ['PLN', 'DE', 100.00, false],
        ];
    }
}
