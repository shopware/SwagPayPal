<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\PayPal\Client;

use Swag\PayPal\PayPal\Client\PayPalClient;
use Swag\PayPal\PayPal\Client\PayPalClientFactory;
use Swag\PayPal\PayPal\PartnerAttributionId;
use Swag\PayPal\Setting\SwagPayPalSettingStruct;
use Swag\PayPal\Test\Mock\CacheMock;
use Swag\PayPal\Test\Mock\PayPal\Resource\TokenResourceMock;

class PayPalClientFactoryMock extends PayPalClientFactory
{
    public const THROW_EXCEPTION = 'throwException';

    /**
     * @var PayPalClientMock
     */
    private $client;

    private $throwException = false;

    public function enableException(): void
    {
        $this->throwException = true;
    }

    public function createPaymentClient(?string $salesChannelId, string $partnerAttributionId = PartnerAttributionId::PAYPAL_CLASSIC): PayPalClient
    {
        $settings = new SwagPayPalSettingStruct();
        $settings->setClientId('testClientId');
        $settings->setClientSecret('testClientSecret');
        $settings->setSandbox(true);

        if ($this->throwException) {
            throw new \RuntimeException('A PayPal test error occurred.');
        }

        $this->client = new PayPalClientMock(
            new TokenResourceMock(
                new CacheMock(),
                new TokenClientFactoryMock()
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
