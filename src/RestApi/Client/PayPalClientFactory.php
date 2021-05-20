<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\Client;

use Psr\Log\LoggerInterface;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\RestApi\BaseURL;
use Swag\PayPal\RestApi\PartnerAttributionId;
use Swag\PayPal\RestApi\V1\Api\OAuthCredentials;
use Swag\PayPal\RestApi\V1\Resource\TokenResourceInterface;
use Swag\PayPal\Setting\Service\SettingsValidationServiceInterface;
use Swag\PayPal\Setting\Settings;

class PayPalClientFactory implements PayPalClientFactoryInterface
{
    private TokenResourceInterface $tokenResource;

    private SettingsValidationServiceInterface $settingsValidationService;

    private SystemConfigService $systemConfigService;

    private LoggerInterface $logger;

    /**
     * @var PayPalClient[]
     */
    private array $payPalClients = [];

    public function __construct(
        TokenResourceInterface $tokenResource,
        SettingsValidationServiceInterface $settingsValidationService,
        SystemConfigService $systemConfigService,
        LoggerInterface $logger
    ) {
        $this->tokenResource = $tokenResource;
        $this->settingsValidationService = $settingsValidationService;
        $this->systemConfigService = $systemConfigService;
        $this->logger = $logger;
    }

    public function getPayPalClient(
        ?string $salesChannelId,
        string $partnerAttributionId = PartnerAttributionId::PAYPAL_CLASSIC
    ): PayPalClientInterface {
        $key = $salesChannelId ?? 'null';

        if (!isset($this->payPalClients[$key])) {
            $this->payPalClients[$key] = new PayPalClient(
                $this->tokenResource,
                null,
                $this->logger,
                $partnerAttributionId,
                $this->createCredentialsObject($salesChannelId)
            );
        }

        return $this->payPalClients[$key];
    }

    private function createCredentialsObject(?string $salesChannelId): OAuthCredentials
    {
        $this->settingsValidationService->validate($salesChannelId);

        $isSandbox = $this->systemConfigService->getBool(Settings::SANDBOX, $salesChannelId);
        $url = $isSandbox ? BaseURL::SANDBOX : BaseURL::LIVE;
        $suffix = $isSandbox ? 'Sandbox' : '';

        $clientId = $this->systemConfigService->getString(Settings::CLIENT_ID . $suffix, $salesChannelId);
        $clientSecret = $this->systemConfigService->getString(Settings::CLIENT_SECRET . $suffix, $salesChannelId);

        $credentials = new OAuthCredentials();
        $credentials->setRestId($clientId);
        $credentials->setRestSecret($clientSecret);
        $credentials->setUrl($url);

        return $credentials;
    }
}
