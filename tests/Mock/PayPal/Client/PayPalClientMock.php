<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\PayPal\Client;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\Client\PayPalClient;
use Swag\PayPal\RestApi\PartnerAttributionId;

/**
 * @internal
 */
#[Package('checkout')]
class PayPalClientMock extends PayPalClient
{
    public function __construct(
        LoggerInterface $logger
    ) {
        parent::__construct([], 'baseUrl', $logger, PartnerAttributionId::PAYPAL_CLASSIC);
        $this->client = new GuzzleClientMock([]);
    }

    public function getData(): array
    {
        if (!$this->client instanceof GuzzleClientMock) {
            return [];
        }

        return $this->client->getData();
    }
}
