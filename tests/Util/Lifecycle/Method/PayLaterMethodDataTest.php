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
use Swag\PayPal\Util\Lifecycle\Method\PayLaterMethodData;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @internal
 */
#[Package('checkout')]
class PayLaterMethodDataTest extends TestCase
{
    private PayLaterMethodData $payLaterMethodData;

    protected function setUp(): void
    {
        $this->payLaterMethodData = new PayLaterMethodData($this->createMock(ContainerInterface::class));
    }

    /**
     * @dataProvider availabilityProvider
     */
    public function testIsAvailable(string $currencyCode, string $countryCode, bool $expected): void
    {
        $availabilityContext = new AvailabilityContext();
        $availabilityContext->assign([
            'currencyCode' => $currencyCode,
            'billingCountryCode' => $countryCode,
        ]);

        static::assertSame($expected, $this->payLaterMethodData->isAvailable($availabilityContext));
    }

    /**
     * @return array<array{string, string, bool}>
     */
    public static function availabilityProvider(): array
    {
        return [
            ['EUR', 'AT', true],
            ['EUR', 'DE', true],
            ['EUR', 'ES', true],
            ['EUR', 'FR', true],
            ['EUR', 'IT', true],
            ['GBP', 'GB', true],
            ['AUD', 'AU', true],
            ['USD', 'US', true],
            ['CAD', 'CA', true],
            ['USD', 'CA', false],
            ['CAD', 'US', false],
            ['EUR', 'CA', false],
            ['ZAR', 'ZA', false],
        ];
    }
}
