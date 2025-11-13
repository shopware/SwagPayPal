<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Webhook\Registration;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Test\Stub\SystemConfigService\StaticSystemConfigService;
use Swag\PayPal\Setting\Service\SettingsValidationService;
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

    private const VALID_SETTINGS = [
        Settings::CLIENT_ID => 'valid-client-id',
        Settings::CLIENT_SECRET => 'valid-client-secret',
        Settings::SANDBOX => false,
        Settings::PAYPAL_CALLBACKS => true,
    ];

    private MockObject&WebhookServiceInterface $webhookService;

    private StaticSystemConfigService $systemConfigService;

    private SettingsValidationService $settingsValidationService;

    private WebhookSystemConfigHelper $helper;

    protected function setUp(): void
    {
        $this->helper = new WebhookSystemConfigHelper(
            new NullLogger(),
            $this->webhookService = $this->createMock(WebhookServiceInterface::class),
            $this->systemConfigService = new StaticSystemConfigService(),
            $this->settingsValidationService = new SettingsValidationService(
                $this->systemConfigService,
                new NullLogger(),
            ),
        );
    }

    /**
     * @param array<string, array<string, mixed>> $newConfig
     * @param array<string, array<string, mixed>> $existingConfig
     */
    #[DataProvider('providercheckWebhookBefore')]
    public function testCheckWebhookBefore(bool $expected, array $newConfig, array $existingConfig): void
    {
        foreach ($existingConfig as $salesChannelId => $config) {
            $salesChannelId = $salesChannelId === 'null' ? null : $salesChannelId;
            $this->systemConfigService->setMultiple($config, $salesChannelId);
        }

        $this->webhookService
            ->expects($expected ? $this->once() : $this->never())
            ->method('deregisterWebhook');

        $this->helper->checkWebhookBefore($newConfig);
    }

    public static function providerCheckWebhookBefore(): \Generator
    {
        yield 'no old distinct settings' => [
            false,
            ['null' => self::VALID_SETTINGS],
            [],
        ];

        yield 'missing actual settings' => [
            false,
            ['null' => self::VALID_SETTINGS],
            ['null' => [Settings::CLIENT_ID => 'old-client-id']],
        ];

        yield 'no actual settings changes' => [
            false,
            ['null' => [Settings::CLIENT_ID => 'same-client-id']],
            ['null' => [Settings::CLIENT_ID => 'same-client-id']],
        ];

        yield 'actual settings changed' => [
            true,
            ['null' => [...self::VALID_SETTINGS, Settings::CLIENT_ID => 'new-client-id']],
            ['null' => self::VALID_SETTINGS],
        ];

        yield 'paypal callbacks disabled' => [
            true,
            ['null' => [...self::VALID_SETTINGS, Settings::CLIENT_ID => 'new-client-id', Settings::PAYPAL_CALLBACKS => false]],
            ['null' => self::VALID_SETTINGS],
        ];
    }

    /**
     * @param array<string, array<string, mixed>> $newConfig
     * @param array<string, array<string, mixed>> $existingConfig
     */
    #[DataProvider('providercheckWebhookAfter')]
    public function testCheckWebhookAfter(bool $expected, array $config): void
    {
        foreach ($config as $salesChannelId => $config) {
            $salesChannelId = $salesChannelId === 'null' ? null : $salesChannelId;
            $this->systemConfigService->setMultiple($config, $salesChannelId);
        }

        $this->webhookService
            ->expects($expected ? $this->once() : $this->never())
            ->method('registerWebhook');

        $this->helper->checkWebhookAfter([null]);
    }

    public static function providerCheckWebhookAfter(): \Generator
    {
        yield 'no valid settings' => [
            false,
            ['null' => [Settings::CLIENT_ID => 'incomplete-client-id']],
        ];

        yield 'valid settings' => [
            true,
            ['null' => self::VALID_SETTINGS],
        ];

        yield 'paypal callbacks disabled' => [
            false,
            ['null' => [...self::VALID_SETTINGS, Settings::PAYPAL_CALLBACKS => false]],
        ];
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
