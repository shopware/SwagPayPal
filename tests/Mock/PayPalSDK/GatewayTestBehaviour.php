<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\PayPalSDK;

use PHPUnit\Framework\Attributes\After;
use Shopware\Core\Framework\Log\Package;
use Shopware\PayPalSDK\Gateway\CustomerGateway;
use Shopware\PayPalSDK\Gateway\OrderGateway;
use Shopware\PayPalSDK\Gateway\PaymentGateway;
use Shopware\PayPalSDK\Gateway\PaymentV1Gateway;
use Shopware\PayPalSDK\Gateway\TokenGateway;
use Shopware\PayPalSDK\Gateway\WebhookGateway;
use Shopware\PayPalSDK\Test\Gateway\TestGateways;
use Shopware\PayPalSDK\Test\Request\TestClient;
use Swag\PayPal\RestApi\RequestService;

/**
 * @internal
 */
#[Package('checkout')]
trait GatewayTestBehaviour
{
    protected static ?TestClient $client = null;

    protected static ?TestGateways $gateways = null;

    public static function getClient(): TestClient
    {
        if (self::$client === null) {
            $handler = new MockRequestHandler();
            self::$client = new TestClient(handler: $handler->handle(...));
        }

        return self::$client;
    }

    public static function customerGateway(): CustomerGateway
    {
        return self::getGateways()->customerGateway();
    }

    public static function paymentGateway(): PaymentGateway
    {
        return self::getGateways()->paymentGateway();
    }

    public static function paymentV1Gateway(): PaymentV1Gateway
    {
        return self::getGateways()->paymentV1Gateway();
    }

    public static function orderGateway(): OrderGateway
    {
        return self::getGateways()->orderGateway();
    }

    public static function tokenGateway(): TokenGateway
    {
        return self::getGateways()->tokenGateway();
    }

    public static function webhookGateway(): WebhookGateway
    {
        return self::getGateways()->webhookGateway();
    }

    protected static function getGateways(): TestGateways
    {
        if (self::$gateways === null) {
            self::$gateways = new TestGateways(self::getClient(), new RequestService());
        }

        return self::$gateways;
    }

    #[After]
    protected function resetClient(): void
    {
        self::$client = null;
        self::$gateways = null;
    }
}
