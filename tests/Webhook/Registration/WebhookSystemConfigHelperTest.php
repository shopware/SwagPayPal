<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Webhook\Registration;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Setting\Service\SettingsValidationServiceInterface;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Webhook\Registration\WebhookSystemConfigHelper;
use Swag\PayPal\Webhook\WebhookServiceInterface;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(WebhookSystemConfigHelper::class)]
class WebhookSystemConfigHelperTest extends TestCase
{
    private const WEBHOOK_KEYS = [
        Settings::CLIENT_ID,
        Settings::CLIENT_SECRET,
        Settings::CLIENT_ID_SANDBOX,
        Settings::CLIENT_SECRET_SANDBOX,
        Settings::SANDBOX,
    ];

    private WebhookSystemConfigHelper $helper;

    protected function setUp(): void
    {
        $this->helper = new WebhookSystemConfigHelper(
            new NullLogger(),
            $this->createMock(WebhookServiceInterface::class),
            $this->createMock(SystemConfigService::class),
            $this->createMock(SettingsValidationServiceInterface::class),
        );
    }

    /**
     * @param array<string, mixed> $config
     */
    #[DataProvider('providerNeedsCheck')]
    public function testNeedsCheck(bool $expected, array $config): void
    {
        static::assertSame($expected, $this->helper->needsCheck($config));
    }

    public static function providerNeedsCheck(): \Generator
    {
        foreach (self::WEBHOOK_KEYS as $key) {
            yield \sprintf('needs check for "%s"', $key) => [
                true,
                [$key => 'some-value'],
            ];

            yield \sprintf('needs check for "%s" with null value', $key) => [
                true,
                [$key => null],
            ];
        }

        foreach (Settings::DEFAULT_VALUES as $key => $value) {
            if (\in_array($key, self::WEBHOOK_KEYS, true)) {
                continue;
            }

            yield \sprintf('no check for "%s"', $key) => [
                false,
                [$key => $value],
            ];
        }
    }
}
