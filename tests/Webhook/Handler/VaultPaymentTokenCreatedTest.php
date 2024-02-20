<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Webhook\Handler;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderCustomer\OrderCustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStructFactory;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use Swag\PayPal\Checkout\Payment\Service\VaultTokenService;
use Swag\PayPal\RestApi\V1\Api\Webhook;
use Swag\PayPal\RestApi\V2\Api\Order;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Card;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\RestApi\V3\Api\PaymentToken;
use Swag\PayPal\Webhook\Handler\VaultPaymentTokenCreated;
use Swag\PayPal\Webhook\WebhookEventTypes;

/**
 * @internal
 */
#[Package('checkout')]
class VaultPaymentTokenCreatedTest extends TestCase
{
    public function testGetEventType(): void
    {
        $handler = new VaultPaymentTokenCreated(
            $this->createMock(EntityRepository::class),
            $this->createMock(OrderTransactionStateHandler::class),
            $this->createMock(VaultTokenService::class),
            $this->createMock(PaymentTransactionStructFactory::class),
            $this->createMock(OrderResource::class),
        );

        static::assertSame(WebhookEventTypes::VAULT_PAYMENT_TOKEN_CREATED, $handler->getEventType());
    }

    public function testInvoke(): void
    {
        $context = Context::createDefaultContext();

        $card = new Card();
        $order = new OrderEntity();
        $orderCustomer = new OrderCustomerEntity();
        $orderCustomer->setCustomerId('customerId');
        $order->setOrderCustomer($orderCustomer);
        $orderTransaction = new OrderTransactionEntity();
        $orderTransaction->setId('orderTransactionId');
        $orderTransaction->setOrder($order);
        $order->setSalesChannelId('salesChannelId');
        $struct = new SyncPaymentTransactionStruct($orderTransaction, $order);

        $payPalOrder = new Order();
        $payPalOrder->assign(['payment_source' => ['card' => $card]]);

        $vaultTokenService = $this->createMock(VaultTokenService::class);
        $vaultTokenService
            ->expects(static::once())
            ->method('saveToken')
            ->with($struct, $card, 'customerId', $context);
        /** @var StaticEntityRepository<OrderTransactionCollection> $orderTransactionRepo */
        $orderTransactionRepo = new StaticEntityRepository([new OrderTransactionCollection([$orderTransaction])]);
        $paymentTransactionStructFactory = $this->createMock(PaymentTransactionStructFactory::class);
        $paymentTransactionStructFactory
            ->expects(static::once())
            ->method('sync')
            ->with($orderTransaction, $order)
            ->willReturn($struct);
        $orderResource = $this->createMock(OrderResource::class);
        $orderResource
            ->expects(static::once())
            ->method('get')
            ->with('00D91479YH268914P', 'salesChannelId')
            ->willReturn($payPalOrder);

        $webhook = new Webhook();
        $webhook->assign($this->getResourceFixture());

        $handler = new VaultPaymentTokenCreated(
            $orderTransactionRepo,
            $this->createMock(OrderTransactionStateHandler::class),
            $vaultTokenService,
            $paymentTransactionStructFactory,
            $orderResource,
        );

        $handler->invoke($webhook, $context);

        static::assertSame('2s74054860464102r', $card->getAttributes()?->getVault()?->getId());
        static::assertSame('PCIUEjnlWD', $card->getAttributes()->getVault()->getCustomer()?->getId());
    }

    public function testInvokeWithoutPayPalOrderId(): void
    {
        $context = Context::createDefaultContext();

        $vaultTokenService = $this->createMock(VaultTokenService::class);
        $vaultTokenService
            ->expects(static::never())
            ->method('saveToken');

        $webhook = new Webhook();
        $webhook->assign($this->getResourceFixture());
        $resource = $webhook->getResource();
        static::assertInstanceOf(PaymentToken::class, $resource);
        $resource->setMetadata(null);

        /** @var StaticEntityRepository<OrderTransactionCollection> $orderTransactionRepo */
        $orderTransactionRepo = new StaticEntityRepository([]);

        $handler = new VaultPaymentTokenCreated(
            $orderTransactionRepo,
            $this->createMock(OrderTransactionStateHandler::class),
            $vaultTokenService,
            $this->createMock(PaymentTransactionStructFactory::class),
            $this->createMock(OrderResource::class),
        );

        $handler->invoke($webhook, $context);
    }

    public function testInvokeWithoutMatchingOrder(): void
    {
        $context = Context::createDefaultContext();

        $vaultTokenService = $this->createMock(VaultTokenService::class);
        $vaultTokenService
            ->expects(static::never())
            ->method('saveToken');

        $webhook = new Webhook();
        $webhook->assign($this->getResourceFixture());

        /** @var StaticEntityRepository<OrderTransactionCollection> $orderTransactionRepo */
        $orderTransactionRepo = new StaticEntityRepository([new OrderTransactionCollection()]);

        $handler = new VaultPaymentTokenCreated(
            $orderTransactionRepo,
            $this->createMock(OrderTransactionStateHandler::class),
            $vaultTokenService,
            $this->createMock(PaymentTransactionStructFactory::class),
            $this->createMock(OrderResource::class),
        );

        $handler->invoke($webhook, $context);
    }

    public function testInvokeWithoutCustomerId(): void
    {
        $context = Context::createDefaultContext();

        $vaultTokenService = $this->createMock(VaultTokenService::class);
        $vaultTokenService
            ->expects(static::never())
            ->method('saveToken');

        $order = new OrderEntity();
        $orderCustomer = new OrderCustomerEntity();
        $order->setOrderCustomer($orderCustomer);
        $orderTransaction = new OrderTransactionEntity();
        $orderTransaction->setId('orderTransactionId');
        $orderTransaction->setOrder($order);

        $webhook = new Webhook();
        $webhook->assign($this->getResourceFixture());

        /** @var StaticEntityRepository<OrderTransactionCollection> $orderTransactionRepo */
        $orderTransactionRepo = new StaticEntityRepository([new OrderTransactionCollection([$orderTransaction])]);

        $handler = new VaultPaymentTokenCreated(
            $orderTransactionRepo,
            $this->createMock(OrderTransactionStateHandler::class),
            $vaultTokenService,
            $this->createMock(PaymentTransactionStructFactory::class),
            $this->createMock(OrderResource::class),
        );

        $handler->invoke($webhook, $context);
    }

    public function testInvokeWithoutVaultablePaymentSource(): void
    {
        $context = Context::createDefaultContext();

        $vaultTokenService = $this->createMock(VaultTokenService::class);
        $vaultTokenService
            ->expects(static::never())
            ->method('saveToken');

        $order = new OrderEntity();
        $orderCustomer = new OrderCustomerEntity();
        $orderCustomer->setCustomerId('customerId');
        $order->setOrderCustomer($orderCustomer);
        $orderTransaction = new OrderTransactionEntity();
        $orderTransaction->setId('orderTransactionId');
        $orderTransaction->setOrder($order);
        $order->setSalesChannelId('salesChannelId');
        $struct = new SyncPaymentTransactionStruct($orderTransaction, $order);

        $webhook = new Webhook();
        $webhook->assign($this->getResourceFixture());

        $paymentTransactionStructFactory = $this->createMock(PaymentTransactionStructFactory::class);
        $paymentTransactionStructFactory
            ->expects(static::once())
            ->method('sync')
            ->with($orderTransaction, $order)
            ->willReturn($struct);
        $orderResource = $this->createMock(OrderResource::class);
        $orderResource
            ->expects(static::once())
            ->method('get')
            ->with('00D91479YH268914P', 'salesChannelId')
            ->willReturn(new Order());

        /** @var StaticEntityRepository<OrderTransactionCollection> $orderTransactionRepo */
        $orderTransactionRepo = new StaticEntityRepository([new OrderTransactionCollection([$orderTransaction])]);

        $handler = new VaultPaymentTokenCreated(
            $orderTransactionRepo,
            $this->createMock(OrderTransactionStateHandler::class),
            $vaultTokenService,
            $paymentTransactionStructFactory,
            $orderResource,
        );

        $handler->invoke($webhook, $context);
    }

    private function getResourceFixture(): array
    {
        return [
            'id' => 'WH-11J22278B8396011J-3A783786PX1819045',
            'event_version' => '1.0',
            'create_time' => '2024-02-15T08:57:10.918Z',
            'resource_type' => 'payment_token',
            'resource_version' => '3.0',
            'event_type' => 'VAULT.PAYMENT-TOKEN.CREATED',
            'summary' => 'A payment token has been created.',
            'resource' => [
                'metadata' => [
                    'order_id' => '00D91479YH268914P',
                ],
                'time_created' => '2024-02-15T00:56:38.367PST',
                'links' => [
                    [
                        'href' => 'https://api.sandbox.paypal.com/v3/vault/payment-tokens/2s74054860464102r',
                        'rel' => 'self',
                        'method' => 'GET',
                        'encType' => 'application/json',
                    ],
                    [
                        'href' => 'https://api.sandbox.paypal.com/v3/vault/payment-tokens/2s74054860464102r',
                        'rel' => 'delete',
                        'method' => 'DELETE',
                        'encType' => 'application/json',
                    ],
                ],
                'id' => '2s74054860464102r',
                'payment_source' => [
                    'card' => [
                        'name' => 'Fritz von Berlichingen',
                        'last_digits' => '1091',
                        'brand' => 'VISA',
                        'expiry' => '2025-12',
                        'billing_address' => [
                            'address_line_1' => 'Albert-Einstein-Ring 2-6',
                            'address_line_2' => 'PayPal',
                            'admin_area_2' => 'Kleinmachnow',
                            'admin_area_1' => 'Brandenburg',
                            'postal_code' => '14532',
                            'country_code' => 'DE',
                        ],
                        'verification_status' => 'VERIFIED',
                        'verification' => [
                            'network_transaction_id' => '100018666069163',
                            'time' => '2024-02-15T00:56:37Z',
                            'amount' => [
                                'currency_code' => 'EUR',
                                'value' => '0.00',
                            ],
                            'processor_response' => [
                                'avs_code' => 'Y',
                                'cvv_code' => 'M',
                                'response_code' => '0000',
                            ],
                            'three_d_secure' => [
                                'type' => 'THREE_DS_AUTHENTICATION',
                                'eci_flag' => 'FULLY_AUTHENTICATED_TRANSACTION',
                                'card_brand' => 'VISA',
                                'enrolled' => 'Y',
                                'pares_status' => 'Y',
                            ],
                        ],
                    ],
                ],
                'customer' => [
                    'id' => 'PCIUEjnlWD',
                ],
            ],
            'links' => [
                [
                    'href' => 'https://api.sandbox.paypal.com/v1/notifications/webhooks-events/WH-11J22278B8396011J-3A783786PX1819045',
                    'rel' => 'self',
                    'method' => 'GET',
                ],
                [
                    'href' => 'https://api.sandbox.paypal.com/v1/notifications/webhooks-events/WH-11J22278B8396011J-3A783786PX1819045/resend',
                    'rel' => 'resend',
                    'method' => 'POST',
                ],
            ],
        ];
    }
}
