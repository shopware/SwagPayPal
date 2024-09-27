<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\PayPal\Client;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\Client\PayPalClientFactoryInterface;
use Swag\PayPal\RestApi\Client\PayPalClientInterface;
use Swag\PayPal\RestApi\PartnerAttributionId;

/**
 * @internal
 */
#[Package('checkout')]
class PayPalClientFactoryMock implements PayPalClientFactoryInterface
{
    private ?PayPalClientMock $client = null;

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public function getPayPalClient(
        ?string $salesChannelId,
        string $partnerAttributionId = PartnerAttributionId::PAYPAL_CLASSIC,
        bool $isFirstParty = false,
    ): PayPalClientInterface {
        if ($this->client === null) {
            $this->client = new PayPalClientMock($this->logger);
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
