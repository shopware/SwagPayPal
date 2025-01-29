<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Setting\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Setting\Service\ApiCredentialService;
use Swag\PayPal\Setting\Service\SettingsSaver;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Webhook\Registration\WebhookSystemConfigHelper;

/**
 * @internal
 */
#[Package('checkout')]
class SettingsSaverTest extends TestCase
{
    private const VALID_LIVE_CREDENTIALS_1 = [
        Settings::CLIENT_ID => 'valid-client-id-1',
        Settings::CLIENT_SECRET => 'valid-client-secret-1',
        Settings::MERCHANT_PAYER_ID => 'valid-merchant-id-1',
    ];

    private const VALID_SANDBOX_CREDENTIALS_1 = [
        Settings::CLIENT_ID_SANDBOX => 'valid-client-id-sandbox-1',
        Settings::CLIENT_SECRET_SANDBOX => 'valid-client-secret-sandbox-1',
        Settings::MERCHANT_PAYER_ID_SANDBOX => 'valid-merchant-id-sandbox-1',
    ];

    private const VALID_LIVE_CREDENTIALS_2 = [
        Settings::CLIENT_ID => 'valid-client-id-2',
        Settings::CLIENT_SECRET => 'valid-client-secret-2',
        Settings::MERCHANT_PAYER_ID => 'valid-merchant-id-2',
    ];

    private const VALID_SANDBOX_CREDENTIALS_2 = [
        Settings::CLIENT_ID_SANDBOX => 'valid-client-id-sandbox-2',
        Settings::CLIENT_SECRET_SANDBOX => 'valid-client-secret-sandbox-2',
        Settings::MERCHANT_PAYER_ID_SANDBOX => 'valid-merchant-id-sandbox-2',
    ];

    private SystemConfigService&MockObject $systemConfigService;

    private ApiCredentialService&MockObject $apiCredentialService;

    private WebhookSystemConfigHelper&MockObject $webhookSystemConfigHelper;

    private SettingsSaver $settingsSaver;

    protected function setUp(): void
    {
        $this->systemConfigService = $this->createMock(SystemConfigService::class);
        $this->apiCredentialService = $this->createMock(ApiCredentialService::class);
        $this->webhookSystemConfigHelper = $this->createMock(WebhookSystemConfigHelper::class);

        $this->settingsSaver = new SettingsSaver(
            $this->systemConfigService,
            $this->apiCredentialService,
            $this->webhookSystemConfigHelper
        );
    }

    /**
     * @param array<string, mixed> $newSettings
     * @param array<string, array<string, mixed>> $oldSettings
     */
    #[DataProvider('saveDataProvider')]
    public function testSave(
        array $newSettings,
        array $oldSettings,
        ?string $salesChannelId,
        bool $liveChanged,
        ?bool $liveValid,
        bool $sandboxChanged,
        ?bool $sandboxValid,
    ): void {
        $this->webhookSystemConfigHelper
            ->expects(static::exactly((int) ($liveValid || $sandboxValid)))
            ->method('checkWebhookBefore')
            ->with([$salesChannelId => $newSettings])
            ->willReturn([]);

        $this->webhookSystemConfigHelper
            ->expects($liveValid || $sandboxValid ? static::once() : static::never())
            ->method('checkWebhookAfter')
            ->with([$salesChannelId])
            ->willReturn([]);

        $this->systemConfigService
            ->expects(static::atLeast(6))
            ->method('get')
            ->willReturnCallback(static function (string $key, ?string $salesChannelId) use ($oldSettings) {
                return $oldSettings[$salesChannelId][$key] ?? null;
            });

        $this->systemConfigService
            ->expects(static::once())
            ->method('setMultiple')
            ->with($newSettings, $salesChannelId);

        $this->apiCredentialService
            ->expects(static::exactly((int) $liveChanged + (int) $sandboxChanged))
            ->method('testApiCredentials')
            ->willReturnCallback(static function (string $clientId, string $clientSecret, bool $sandbox, string $merchantId) use ($liveValid, $sandboxValid) {
                $validExpected = $sandbox ? $sandboxValid : $liveValid;
                $credentialsValid = \str_contains($clientId, 'valid')
                    && \str_contains($clientSecret, 'valid')
                    && \str_contains($merchantId, 'valid');

                return $validExpected && $credentialsValid;
            });

        $information = $this->settingsSaver->save($newSettings, $salesChannelId);

        static::assertSame(
            $liveChanged,
            $information->getLiveCredentialsChanged(),
            'Expected live credentials to be ' . ($liveChanged ? 'changed' : 'not changed'),
        );
        static::assertSame(
            $liveValid,
            $information->getLiveCredentialsValid(),
            'Expected live credentials to be ' . ($liveValid ? 'valid' : 'invalid'),
        );
        static::assertSame(
            $sandboxChanged,
            $information->getSandboxCredentialsChanged(),
            'Expected sandbox credentials to be ' . ($sandboxChanged ? 'changed' : 'not changed'),
        );
        static::assertSame(
            $sandboxValid,
            $information->getSandboxCredentialsValid(),
            'Expected sandbox credentials to be ' . ($sandboxValid ? 'valid' : 'invalid'),
        );
    }

    public static function saveDataProvider(): \Generator
    {
        yield 'save simple settings' => [
            'newSettings' => [Settings::BRAND_NAME => 'testBrandName'],
            'oldSettings' => [null => []],
            'salesChannelId' => null,
            'liveChanged' => false,
            'liveValid' => null,
            'sandboxChanged' => false,
            'sandboxValid' => null,
        ];

        yield 'added live credentials' => [
            'newSettings' => self::VALID_LIVE_CREDENTIALS_1,
            'oldSettings' => [null => []],
            'salesChannelId' => null,
            'liveChanged' => true,
            'liveValid' => true,
            'sandboxChanged' => false,
            'sandboxValid' => null,
        ];

        yield 'added sandbox credentials' => [
            'newSettings' => self::VALID_SANDBOX_CREDENTIALS_1,
            'oldSettings' => [null => []],
            'salesChannelId' => null,
            'liveChanged' => false,
            'liveValid' => null,
            'sandboxChanged' => true,
            'sandboxValid' => true,
        ];

        yield 'added live + sandbox credentials' => [
            'newSettings' => [
                ...self::VALID_LIVE_CREDENTIALS_1,
                ...self::VALID_SANDBOX_CREDENTIALS_1,
            ],
            'oldSettings' => [null => []],
            'salesChannelId' => null,
            'liveChanged' => true,
            'liveValid' => true,
            'sandboxChanged' => true,
            'sandboxValid' => true,
        ];

        yield 'changed live credentials' => [
            'newSettings' => [
                ...self::VALID_LIVE_CREDENTIALS_2,
                ...self::VALID_SANDBOX_CREDENTIALS_1,
            ],
            'oldSettings' => [null => [
                ...self::VALID_LIVE_CREDENTIALS_1,
                ...self::VALID_SANDBOX_CREDENTIALS_1,
            ]],
            'salesChannelId' => null,
            'liveChanged' => true,
            'liveValid' => true,
            'sandboxChanged' => false,
            'sandboxValid' => null,
        ];

        yield 'changed sandbox credentials' => [
            'newSettings' => [
                ...self::VALID_LIVE_CREDENTIALS_1,
                ...self::VALID_SANDBOX_CREDENTIALS_2,
            ],
            'oldSettings' => [null => [
                ...self::VALID_LIVE_CREDENTIALS_1,
                ...self::VALID_SANDBOX_CREDENTIALS_1,
            ]],
            'salesChannelId' => null,
            'liveChanged' => false,
            'liveValid' => null,
            'sandboxChanged' => true,
            'sandboxValid' => true,
        ];

        yield 'changed live + sandbox credentials' => [
            'newSettings' => [
                ...self::VALID_LIVE_CREDENTIALS_1,
                ...self::VALID_SANDBOX_CREDENTIALS_1,
            ],
            'oldSettings' => [null => [
                ...self::VALID_LIVE_CREDENTIALS_2,
                ...self::VALID_SANDBOX_CREDENTIALS_2,
            ]],
            'salesChannelId' => null,
            'liveChanged' => true,
            'liveValid' => true,
            'sandboxChanged' => true,
            'sandboxValid' => true,
        ];

        yield 'changed live credentials to be invalid' => [
            'newSettings' => [
                ...self::VALID_LIVE_CREDENTIALS_1,
                ...self::VALID_SANDBOX_CREDENTIALS_1,
                Settings::CLIENT_ID => null,
            ],
            'oldSettings' => [null => [
                ...self::VALID_LIVE_CREDENTIALS_1,
                ...self::VALID_SANDBOX_CREDENTIALS_1,
            ]],
            'salesChannelId' => null,
            'liveChanged' => true,
            'liveValid' => false,
            'sandboxChanged' => false,
            'sandboxValid' => null,
        ];

        yield 'changed sandbox credentials to be invalid' => [
            'newSettings' => [
                ...self::VALID_LIVE_CREDENTIALS_1,
                ...self::VALID_SANDBOX_CREDENTIALS_1,
                Settings::CLIENT_ID_SANDBOX => null,
            ],
            'oldSettings' => [null => [
                ...self::VALID_LIVE_CREDENTIALS_1,
                ...self::VALID_SANDBOX_CREDENTIALS_1,
            ]],
            'salesChannelId' => null,
            'liveChanged' => false,
            'liveValid' => null,
            'sandboxChanged' => true,
            'sandboxValid' => false,
        ];

        yield 'changed live + sandbox credentials to be invalid' => [
            'newSettings' => [
                ...self::VALID_LIVE_CREDENTIALS_1,
                ...self::VALID_SANDBOX_CREDENTIALS_1,
                Settings::CLIENT_ID => null,
                Settings::CLIENT_ID_SANDBOX => null,
            ],
            'oldSettings' => [null => [
                ...self::VALID_LIVE_CREDENTIALS_1,
                ...self::VALID_SANDBOX_CREDENTIALS_1,
            ]],
            'salesChannelId' => null,
            'liveChanged' => true,
            'liveValid' => false,
            'sandboxChanged' => true,
            'sandboxValid' => false,
        ];

        yield 'added partial live credentials will inherit' => [
            'newSettings' => [
                Settings::CLIENT_ID => 'valid-client-id',
            ],
            'oldSettings' => [null => [
                ...self::VALID_LIVE_CREDENTIALS_1,
                Settings::CLIENT_ID => null,
            ]],
            'salesChannelId' => 'sales-channel-id',
            'liveChanged' => true,
            'liveValid' => true,
            'sandboxChanged' => false,
            'sandboxValid' => null,
        ];

        yield 'added partial sandbox credentials will inherit' => [
            'newSettings' => [
                Settings::CLIENT_ID_SANDBOX => 'valid-client-id-sandbox',
            ],
            'oldSettings' => [null => [
                ...self::VALID_SANDBOX_CREDENTIALS_1,
                Settings::CLIENT_ID_SANDBOX => null,
            ]],
            'salesChannelId' => 'sales-channel-id',
            'liveChanged' => false,
            'liveValid' => null,
            'sandboxChanged' => true,
            'sandboxValid' => true,
        ];

        yield 'added partial live + sandbox credentials will inherit' => [
            'newSettings' => [
                Settings::CLIENT_ID => 'valid-client-id',
                Settings::CLIENT_ID_SANDBOX => 'valid-client-id-sandbox',
            ],
            'oldSettings' => [null => [
                ...self::VALID_LIVE_CREDENTIALS_1,
                ...self::VALID_SANDBOX_CREDENTIALS_1,
                Settings::CLIENT_ID => null,
                Settings::CLIENT_ID_SANDBOX => null,
            ]],
            'salesChannelId' => 'sales-channel-id',
            'liveChanged' => true,
            'liveValid' => true,
            'sandboxChanged' => true,
            'sandboxValid' => true,
        ];

        yield 'added partial invalid live + sandbox credentials will inherit' => [
            'newSettings' => [
                Settings::CLIENT_ID => 'incorrect',
                Settings::CLIENT_ID_SANDBOX => 'incorrect',
            ],
            'oldSettings' => [null => [
                ...self::VALID_LIVE_CREDENTIALS_1,
                ...self::VALID_SANDBOX_CREDENTIALS_1,
            ]],
            'salesChannelId' => 'sales-channel-id',
            'liveChanged' => true,
            'liveValid' => false,
            'sandboxChanged' => true,
            'sandboxValid' => false,
        ];
    }

    public function testWebhookErrors(): void
    {
        $salesChannelId = null;
        $newSettings = self::VALID_LIVE_CREDENTIALS_1;
        $errorsBefore = [new \RuntimeException('error-before')];
        $errorsAfter = [new \RuntimeException('error-after')];

        $this->webhookSystemConfigHelper
            ->expects(static::once())
            ->method('checkWebhookBefore')
            ->with([$salesChannelId => $newSettings])
            ->willReturn($errorsBefore);

        $this->webhookSystemConfigHelper
            ->expects(static::once())
            ->method('checkWebhookAfter')
            ->with([$salesChannelId])
            ->willReturn($errorsAfter);

        $this->systemConfigService
            ->method('get')
            ->willReturn(null);

        $this->systemConfigService
            ->expects(static::once())
            ->method('setMultiple')
            ->with($newSettings, $salesChannelId);

        $this->apiCredentialService
            ->expects(static::once())
            ->method('testApiCredentials')
            ->willReturn(true);

        $information = $this->settingsSaver->save($newSettings, $salesChannelId);

        static::assertTrue($information->getLiveCredentialsChanged());
        static::assertTrue($information->getLiveCredentialsValid());
        static::assertEquals(['error-before', 'error-after'], $information->getWebhookErrors());
    }

    public function testApiCredentialsValidationError(): void
    {
        $salesChannelId = null;
        $newSettings = self::VALID_LIVE_CREDENTIALS_1;

        $this->webhookSystemConfigHelper
            ->expects(static::never())
            ->method('checkWebhookBefore')
            ->with([$salesChannelId => $newSettings])
            ->willReturn([]);

        $this->webhookSystemConfigHelper
            ->expects(static::never())
            ->method('checkWebhookAfter')
            ->with([$salesChannelId])
            ->willReturn([]);

        $this->systemConfigService
            ->method('get')
            ->willReturn(null);

        $this->systemConfigService
            ->expects(static::once())
            ->method('setMultiple')
            ->with($newSettings, $salesChannelId);

        $this->apiCredentialService
            ->expects(static::once())
            ->method('testApiCredentials')
            ->willThrowException(new \RuntimeException('error'));

        $information = $this->settingsSaver->save($newSettings, $salesChannelId);

        static::assertTrue($information->getLiveCredentialsChanged());
        static::assertFalse($information->getLiveCredentialsValid());
    }
}
