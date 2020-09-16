<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\PayPal\Client;

use Psr\Log\LoggerInterface;
use Swag\PayPal\RestApi\Client\PayPalClientFactoryInterface;
use Swag\PayPal\RestApi\Client\PayPalClientInterface;
use Swag\PayPal\RestApi\PartnerAttributionId;
use Swag\PayPal\RestApi\V1\Resource\TokenResource;
use Swag\PayPal\RestApi\V1\Service\TokenValidator;
use Swag\PayPal\Setting\Service\SettingsServiceInterface;
use Swag\PayPal\Test\Mock\CacheMock;

class PayPalClientFactoryMock implements PayPalClientFactoryInterface
{
    /**
     * @var PayPalClientMock|null
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
        SettingsServiceInterface $settingsService,
        LoggerInterface $logger
    ) {
        $this->settingsService = $settingsService;
        $this->logger = $logger;
    }

    public function getPayPalClient(
        ?string $salesChannelId,
        string $partnerAttributionId = PartnerAttributionId::PAYPAL_CLASSIC
    ): PayPalClientInterface {
        $settings = $this->settingsService->getSettings($salesChannelId);

        if ($this->client === null) {
            $this->client = new PayPalClientMock(
                new TokenResource(
                    new CacheMock(),
                    new TokenClientFactoryMock($this->logger),
                    new TokenValidator()
                ),
                $settings,
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
}
