<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\PayPal\Client;

use Psr\Log\LoggerInterface;
use Swag\PayPal\RestApi\Client\PayPalClient;
use Swag\PayPal\RestApi\V1\Resource\TokenResource;
use Swag\PayPal\Setting\SwagPayPalSettingStruct;

class PayPalClientMock extends PayPalClient
{
    public function __construct(
        TokenResource $tokenResource,
        SwagPayPalSettingStruct $settings,
        LoggerInterface $logger
    ) {
        parent::__construct($tokenResource, $settings, $logger);
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
