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
use Swag\PayPal\Test\Mock\PayPal\Resource\TokenResourceMock;
use Swag\PayPal\Test\Payment\PayPalPaymentHandlerTest;

class PayPalClientFactoryMock extends PayPalClientFactory
{
    /**
     * @var PayPalClientMock
     */
    private $client;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        TokenResource $tokenResource,
        SettingsServiceInterface $settingsProvider,
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
        parent::__construct($tokenResource, $settingsProvider, $logger);
    }

    public function createPaymentClient(
        ?string $salesChannelId,
        string $partnerAttributionId = PartnerAttributionId::PAYPAL_CLASSIC
    ): PayPalClient {
        $settings = $this->settingsProvider->getSettings($salesChannelId);

        if ($settings->hasExtension(PayPalPaymentHandlerTest::PAYPAL_RESOURCE_THROWS_EXCEPTION)) {
            throw new \RuntimeException('A PayPal test error occurred.');
        }

        $this->client = new PayPalClientMock(
            new TokenResourceMock(
                new CacheMock(),
                new TokenClientFactoryMock($this->logger),
                new CredentialsClientFactoryMock($this->logger)
            ),
            $settings
        );

        return $this->client;
    }

    public function getClient(): PayPalClientMock
    {
        return $this->client;
    }
}
