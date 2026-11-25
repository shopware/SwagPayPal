<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Setting\Service;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Setting\Struct\SettingsInformationStruct;
use Swag\PayPal\Util\Log\Log;
use Swag\PayPal\Util\Log\LogCode;
use Swag\PayPal\Webhook\Registration\WebhookSystemConfigHelper;

#[Package('checkout')]
class SettingsSaver implements SettingsSaverInterface
{
    /**
     * @internal
     */
    public function __construct(
        private readonly SystemConfigService $systemConfigService,
        private readonly ApiCredentialService $apiCredentialService,
        private readonly WebhookSystemConfigHelper $webhookSystemConfigHelper,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @param array<string, mixed> $settings
     */
    public function save(array $settings, ?string $salesChannelId = null): SettingsInformationStruct
    {
        $information = new SettingsInformationStruct();

        $old = $this->getCredentials($salesChannelId);

        $information->setLiveCredentialsChanged(
            $this->credentialsChanged($settings, $old, Settings::LIVE_CREDENTIAL_KEYS),
        );
        $information->setSandboxCredentialsChanged(
            $this->credentialsChanged($settings, $old, Settings::SANDBOX_CREDENTIAL_KEYS),
        );

        if ($information->getLiveCredentialsChanged()) {
            $information->setLiveCredentialsValid(
                $this->testCredentialsLive($settings, $salesChannelId),
            );
        }

        if ($information->getSandboxCredentialsChanged()) {
            $information->setSandboxCredentialsValid(
                $this->testCredentialsSandbox($settings, $salesChannelId),
            );
        }

        if ($information->getLiveCredentialsValid() || $information->getSandboxCredentialsValid()) {
            $webhookErrors = $this->webhookSystemConfigHelper->checkWebhookBefore([$salesChannelId => $settings]);
        }

        $changedKeys = $this->getChangedKeys($settings, $salesChannelId);

        $this->systemConfigService->setMultiple($settings, $salesChannelId);

        if ($changedKeys !== []) {
            $this->logger->info('Saved the PayPal settings, changed: {changedKeys}', Log::context(
                code: LogCode::SETTINGS_SAVED,
                salesChannelId: $salesChannelId,
                changedKeys: \str_replace(Settings::SYSTEM_CONFIG_DOMAIN, '', \implode(', ', $changedKeys)),
                sandbox: $this->systemConfigService->getBool(Settings::SANDBOX, $salesChannelId),
            ));
        }

        if ($information->getLiveCredentialsValid() || $information->getSandboxCredentialsValid()) {
            $webhookErrors = \array_merge(
                $webhookErrors ?? [],
                $this->webhookSystemConfigHelper->checkWebhookAfter([$salesChannelId]),
            );
        }

        $information->setWebhookErrors(\array_map(static fn (\Throwable $e) => $e->getMessage(), $webhookErrors ?? []));

        return $information;
    }

    /**
     * The Administration sends null for inherited values: that's only a change if the sales channel had its own value.
     *
     * @param array<string, mixed> $settings
     *
     * @return list<string>
     */
    private function getChangedKeys(array $settings, ?string $salesChannelId): array
    {
        return \array_keys(\array_filter(
            $settings,
            fn (mixed $value, string $key) => $this->systemConfigService->get($key, $salesChannelId) !== ($value ?? ($salesChannelId === null ? null : $this->systemConfigService->get($key))),
            \ARRAY_FILTER_USE_BOTH,
        ));
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function testCredentialsLive(array $settings, ?string $salesChannelId): bool
    {
        $credentials = $this->filterSettings($settings, Settings::LIVE_CREDENTIAL_KEYS);

        // Inherit potentially missing credentials
        if ($salesChannelId) {
            $credentials[Settings::CLIENT_ID] ??= $this->systemConfigService->get(Settings::CLIENT_ID);
            $credentials[Settings::CLIENT_SECRET] ??= $this->systemConfigService->get(Settings::CLIENT_SECRET);
            $credentials[Settings::MERCHANT_PAYER_ID] ??= $this->systemConfigService->get(Settings::MERCHANT_PAYER_ID);
        }

        return $this->testCredentials(
            $credentials[Settings::CLIENT_ID] ?? '',
            $credentials[Settings::CLIENT_SECRET] ?? '',
            $credentials[Settings::MERCHANT_PAYER_ID] ?? '',
            false,
            $salesChannelId,
        );
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function testCredentialsSandbox(array $settings, ?string $salesChannelId): bool
    {
        $credentials = $this->filterSettings($settings, Settings::SANDBOX_CREDENTIAL_KEYS);

        // Inherit potentially missing credentials
        if ($salesChannelId) {
            $credentials[Settings::CLIENT_ID_SANDBOX] ??= $this->systemConfigService->get(Settings::CLIENT_ID_SANDBOX);
            $credentials[Settings::CLIENT_SECRET_SANDBOX] ??= $this->systemConfigService->get(Settings::CLIENT_SECRET_SANDBOX);
            $credentials[Settings::MERCHANT_PAYER_ID_SANDBOX] ??= $this->systemConfigService->get(Settings::MERCHANT_PAYER_ID_SANDBOX);
        }

        return $this->testCredentials(
            $credentials[Settings::CLIENT_ID_SANDBOX] ?? '',
            $credentials[Settings::CLIENT_SECRET_SANDBOX] ?? '',
            $credentials[Settings::MERCHANT_PAYER_ID_SANDBOX] ?? '',
            true,
            $salesChannelId,
        );
    }

    private function testCredentials(string $clientId, string $clientSecret, string $merchantId, bool $sandbox, ?string $salesChannelId): bool
    {
        try {
            return $this->apiCredentialService->testApiCredentials($clientId, $clientSecret, $sandbox, $merchantId);
        } catch (\Exception $e) {
            $this->logger->warning('Could not verify the PayPal credentials, the Administration shows them as invalid', Log::context(
                code: LogCode::SETTINGS_CREDENTIALS_UNVERIFIED,
                salesChannelId: $salesChannelId,
                sandbox: $sandbox,
                exception: $e,
            ));

            return false;
        }
    }

    private function getCredentials(?string $salesChannelId): array
    {
        return \array_combine(
            Settings::CREDENTIAL_KEYS,
            \array_map(
                fn (string $key) => $this->systemConfigService->get($key, $salesChannelId),
                Settings::CREDENTIAL_KEYS,
            ),
        );
    }

    /**
     * @param array<string, mixed> $new
     * @param array<string, mixed> $old
     * @param list<string> $keys
     */
    private function credentialsChanged(array $new, array $old, array $keys): bool
    {
        $new = $this->filterSettings($new, $keys);
        $old = $this->filterSettings($old, $keys);

        return \count(\array_diff($new, $old)) > 0;
    }

    /**
     * @param array<string, mixed> $kvs
     * @param list<string> $keys
     *
     * @return array<string, mixed>
     */
    private function filterSettings(array $kvs, array $keys): array
    {
        return \array_combine($keys, \array_map(
            static fn (string $key) => $kvs[$key] ?? null,
            $keys,
        ));
    }
}
