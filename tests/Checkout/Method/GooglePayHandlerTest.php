<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\Method;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Shopware\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use Shopware\PayPalSDK\Struct\V2\Order;
use Swag\PayPal\Checkout\Card\GooglePayValidator;
use Swag\PayPal\Checkout\Payment\Method\AbstractPaymentMethodHandler;
use Swag\PayPal\Checkout\Payment\Method\GooglePayHandler;
use Swag\PayPal\Checkout\Payment\Service\OrderExecuteService;
use Swag\PayPal\Checkout\Payment\Service\OrderPatchService;
use Swag\PayPal\Checkout\Payment\Service\TransactionDataService;
use Swag\PayPal\Checkout\Payment\Service\VaultTokenService;
use Swag\PayPal\OrdersApi\Builder\AbstractOrderBuilder;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\Setting\Service\SettingsValidationServiceInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(GooglePayHandler::class)]
class GooglePayHandlerTest extends TestCase
{
    private MockObject&OrderResource $orderResource;

    private MockObject&GooglePayValidator $cardValidator;

    private GooglePayHandler $googlePayHandler;

    /**
     * @var StaticEntityRepository<OrderTransactionCollection>
     */
    private StaticEntityRepository $orderTransactionRepo;

    protected function setUp(): void
    {
        $this->orderResource = $this->createMock(OrderResource::class);
        $this->cardValidator = $this->createMock(GooglePayValidator::class);

        /** @var StaticEntityRepository<OrderTransactionCollection> $orderTransactionRepo */
        $orderTransactionRepo = new StaticEntityRepository([], new OrderTransactionDefinition());
        $this->orderTransactionRepo = $orderTransactionRepo;

        $this->googlePayHandler = new GooglePayHandler(
            $this->createMock(SettingsValidationServiceInterface::class),
            $this->createMock(StateMachineRegistry::class),
            $this->createMock(OrderExecuteService::class),
            $this->createMock(OrderPatchService::class),
            $this->createMock(TransactionDataService::class),
            $this->orderResource,
            $this->createMock(VaultTokenService::class),
            $this->orderTransactionRepo,
            $this->createMock(AbstractOrderBuilder::class),
            $this->cardValidator,
        );
    }

    public function testExecuteOrderWithoutValid3DSThrowsException(): void
    {
        $order = (new Order())->assign([
            'id' => 'paypalOrderId',
            'payment_source' => ['google_pay' => ['card' => ['authentication_result' => [
                'liability_shift' => 'no',
                'three_d_secure' => null,
            ]]]],
            'links' => [['rel' => 'self', 'href' => 'https://example.com']],
        ]);

        $this->orderResource->method('get')->willReturn($order);

        $this->cardValidator->method('validate')->willReturn(false);

        static::expectException(PaymentException::class);
        static::expectExceptionMessage('Credit card validation failed, 3D secure was not validated.');

        $orderTransaction = new OrderTransactionEntity();
        $orderTransaction->setId(Uuid::randomHex());
        $order = new OrderEntity();
        $order->setId(Uuid::randomHex());
        $order->setSalesChannelId(Uuid::randomHex());
        $orderTransaction->setOrder($order);
        $this->orderTransactionRepo->addSearch([$orderTransaction]);

        $this->googlePayHandler->pay(
            new Request([], [AbstractPaymentMethodHandler::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME => 'paypalOrderId']),
            new PaymentTransactionStruct($orderTransaction->getId()),
            Context::createDefaultContext(),
            null
        );
    }
}
