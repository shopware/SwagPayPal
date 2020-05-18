<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\PayPal\Client;

use Psr\Log\LoggerInterface;
use Swag\PayPal\PayPal\Client\PayPalClient;
use Swag\PayPal\PayPal\Client\PayPalClientFactory;
use Swag\PayPal\PayPal\PartnerAttributionId;
use Swag\PayPal\PayPal\Resource\TokenResource;
use Swag\PayPal\Setting\Service\SettingsServiceInterface;
use Swag\PayPal\Test\Mock\CacheMock;

class PayPalClientFactoryMock extends PayPalClientFactory
{
    /**
     * @var PayPalClientMock
     */
    private $client;

    /**
     * @var SettingsServiceInterface
     */
    private $settingsService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        TokenResource $tokenResource,
        SettingsServiceInterface $settingsService,
        LoggerInterface $logger
    ) {
        $this->settingsService = $settingsService;
        $this->logger = $logger;
        parent::__construct($tokenResource, $settingsService, $logger);
    }

    public function createPaymentClient(
        ?string $salesChannelId,
        string $partnerAttributionId = PartnerAttributionId::PAYPAL_CLASSIC
    ): PayPalClient {
        $settings = $this->settingsService->getSettings($salesChannelId);

        $this->client = new PayPalClientMock(
            new TokenResource(
                new CacheMock(),
                new TokenClientFactoryMock($this->logger),
                new CredentialsClientFactoryMock($this->logger)
            ),
            $settings,
            $this->logger
        );

        return $this->client;
    }

    public function getClient(): PayPalClientMock
    {
        return $this->client;
    }
}
