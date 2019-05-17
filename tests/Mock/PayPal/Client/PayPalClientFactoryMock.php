<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\PayPal\Client;

use Shopware\Core\Framework\Context;
use Swag\PayPal\PayPal\Client\PayPalClient;
use Swag\PayPal\PayPal\Client\PayPalClientFactory;
use Swag\PayPal\Setting\SwagPayPalSettingGeneralStruct;
use Swag\PayPal\Test\Mock\CacheMock;
use Swag\PayPal\Test\Mock\PayPal\Resource\TokenResourceMock;
use Swag\PayPal\Test\Payment\PayPalPaymentHandlerTest;

class PayPalClientFactoryMock extends PayPalClientFactory
{
    public const THROW_EXCEPTION = 'throwException';

    /**
     * @var PayPalClientMock
     */
    private $client;

    public function createPaymentClient(Context $context): PayPalClient
    {
        $settings = new SwagPayPalSettingGeneralStruct();
        $settings->setClientId('testClientId');
        $settings->setClientSecret('testClientSecret');
        $settings->setSandbox(true);

        $cacheId = 'test';
        if ($context->hasExtension(PayPalPaymentHandlerTest::PAYPAL_RESOURCE_THROWS_EXCEPTION)) {
            $cacheId = self::THROW_EXCEPTION;
        }

        $this->client = new PayPalClientMock(
            new TokenResourceMock(
                new CacheMock(),
                new TokenClientFactoryMock()
            ),
            $settings,
            $cacheId
        );

        return $this->client;
    }

    public function getClient(): PayPalClientMock
    {
        return $this->client;
    }
}
