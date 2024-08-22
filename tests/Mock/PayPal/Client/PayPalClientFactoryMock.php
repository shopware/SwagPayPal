<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\PayPal\Client;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\RestApi\BaseURL;
use Swag\PayPal\RestApi\Client\PayPalClientFactoryInterface;
use Swag\PayPal\RestApi\Client\PayPalClientInterface;
use Swag\PayPal\RestApi\PartnerAttributionId;
use Swag\PayPal\RestApi\V1\Api\OAuthCredentials;
use Swag\PayPal\RestApi\V1\Resource\TokenResource;
use Swag\PayPal\RestApi\V1\Service\TokenValidator;
use Swag\PayPal\Setting\Service\SettingsValidationService;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Test\Mock\CacheMock;

/**
 * @internal
 */
#[Package('checkout')]
class PayPalClientFactoryMock implements PayPalClientFactoryInterface
{
    private ?PayPalClientMock $client = null;

    private SystemConfigService $systemConfigService;

    private LoggerInterface $logger;

    public function __construct(
        SystemConfigService $systemConfigService,
        LoggerInterface $logger
    ) {
        $this->systemConfigService = $systemConfigService;
        $this->logger = $logger;
    }

    public function getPayPalClient(
        ?string $salesChannelId,
        string $partnerAttributionId = PartnerAttributionId::PAYPAL_CLASSIC
    ): PayPalClientInterface {
        if ($this->client === null) {
            $this->client = new PayPalClientMock(
                new TokenResource(
                    new CacheMock(),
                    new TokenClientFactoryMock($this->logger),
                    new TokenValidator()
                ),
                $this->createCredentialsObject($salesChannelId),
                $this->logger
            );
        }

        return $this->client;
    }

    public function getClient(): PayPalClientMock
    {
        if ($this->client === null) {
            throw new \RuntimeException('Something went wrong. There is no client');
        }

        return $this->client;
    }

    private function createCredentialsObject(?string $salesChannelId): OAuthCredentials
    {
        $validation = new SettingsValidationService($this->systemConfigService, new NullLogger());
        $validation->validate($salesChannelId);

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
