<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Util\Lifecycle\Method;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Checkout\Payment\Method\PayLaterHandler;
use Swag\PayPal\RestApi\V1\Api\MerchantIntegrations;
use Swag\PayPal\Storefront\Data\Service\PayLaterCheckoutDataService;
use Swag\PayPal\Util\Availability\AvailabilityContext;
use Swag\PayPal\Util\Lifecycle\Method\PayLaterMethodData;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @internal
 */
#[Package('checkout'), CoversClass(PayLaterMethodData::class)]
class PayLaterMethodDataTest extends TestCase
{
    private PayLaterMethodData $payLaterMethodData;

    private MockObject&ContainerInterface $container;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->payLaterMethodData = new PayLaterMethodData($this->container);
    }

    public function testGetTranslations(): void
    {
        $expected = [
            'de-DE' => [
                'description' => 'Jetzt kaufen und später bezahlen - unterstützt von Paypal',
                'name' => 'Später Bezahlen',
            ],
            'en-GB' => [
                'description' => 'Buy now and pay later - provided by Paypal',
                'name' => 'Pay Later',
            ],
        ];

        static::assertSame($expected, $this->payLaterMethodData->getTranslations());
    }

    public function testGetPosition(): void
    {
        static::assertSame(-99, $this->payLaterMethodData->getPosition());
    }

    public function testGetHandler(): void
    {
        static::assertSame(PayLaterHandler::class, $this->payLaterMethodData->getHandler());
    }

    public function testGetTechnicalName(): void
    {
        static::assertSame('swag_paypal_pay_later', $this->payLaterMethodData->getTechnicalName());
    }

    #[DataProvider('availabilityProvider')]
    public function testIsAvailable(string $currencyCode, string $countryCode, float $totalAmount, bool $expected): void
    {
        $availabilityContext = new AvailabilityContext();
        $availabilityContext->assign([
            'currencyCode' => $currencyCode,
            'billingCountryCode' => $countryCode,
            'totalAmount' => $totalAmount,
        ]);

        static::assertSame($expected, $this->payLaterMethodData->isAvailable($availabilityContext));
    }

    public function testGetInitialState(): void
    {
        static::assertTrue($this->payLaterMethodData->getInitialState());
    }

    public function testGetMediaFileName(): void
    {
        static::assertSame('paypal', $this->payLaterMethodData->getMediaFileName());
    }

    public function testGetCheckoutDataService(): void
    {
        $payLaterCheckoutDataService = $this->createMock(PayLaterCheckoutDataService::class);
        $this->container->expects(static::once())
            ->method('get')
            ->with(PayLaterCheckoutDataService::class)
            ->willReturn($payLaterCheckoutDataService);

        static::assertSame($payLaterCheckoutDataService, $this->payLaterMethodData->getCheckoutDataService());
    }

    public function testGetCheckoutTemplateExtensionId(): void
    {
        static::assertSame('payPalPayLaterFieldData', $this->payLaterMethodData->getCheckoutTemplateExtensionId());
    }

    public function testValidateCapability(): void
    {
        $merchantIntegrations = $this->createMock(MerchantIntegrations::class);

        static::assertSame('active', $this->payLaterMethodData->validateCapability($merchantIntegrations));
    }

    public static function availabilityProvider(): array
    {
        return [
            ['ZAR', 'ZA', 1000.00, false],
            ['EUR', 'DE', 50.00, true],
            ['EUR', 'DE', 0.50, false],
            ['EUR', 'DE', 11000.00, false],
            ['USD', 'US', 100.00, true],
            ['USD', 'US', 160000.00, false],
            ['GBP', 'GB', 50.00, true],
            ['GBP', 'GB', 10.00, false],
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
