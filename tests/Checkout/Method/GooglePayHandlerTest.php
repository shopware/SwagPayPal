<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\Method;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Checkout\Test\Cart\Common\Generator;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Swag\PayPal\Checkout\Card\GooglePayValidator;
use Swag\PayPal\Checkout\Payment\Method\AbstractPaymentMethodHandler;
use Swag\PayPal\Checkout\Payment\Method\GooglePayHandler;
use Swag\PayPal\Checkout\Payment\Service\OrderExecuteService;
use Swag\PayPal\Checkout\Payment\Service\OrderPatchService;
use Swag\PayPal\Checkout\Payment\Service\TransactionDataService;
use Swag\PayPal\Checkout\Payment\Service\VaultTokenService;
use Swag\PayPal\RestApi\V2\Api\Order;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\Setting\Service\SettingsValidationServiceInterface;

/**
 * @internal
 *
 * @covers \Swag\PayPal\Checkout\Payment\Method\GooglePayHandler
 */
#[Package('checkout')]
class GooglePayHandlerTest extends TestCase
{
    private MockObject&OrderResource $orderResource;

    private MockObject&GooglePayValidator $cardValidator;

    private GooglePayHandler $googlePayHandler;

    protected function setUp(): void
    {
        $this->orderResource = $this->createMock(OrderResource::class);
        $this->cardValidator = $this->createMock(GooglePayValidator::class);

        $this->googlePayHandler = new GooglePayHandler(
            $this->createMock(SettingsValidationServiceInterface::class),
            $this->createMock(OrderTransactionStateHandler::class),
            $this->createMock(OrderExecuteService::class),
            $this->createMock(OrderPatchService::class),
            $this->createMock(TransactionDataService::class),
            $this->createMock(LoggerInterface::class),
            $this->orderResource,
            $this->createMock(VaultTokenService::class),
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
        ]);

        $this->orderResource->method('get')->willReturn($order);

        $this->cardValidator->method('validate')->willReturn(false);

        static::expectException(PaymentException::class);
        static::expectExceptionMessage('Credit card validation failed, 3D secure was not validated.');

        $this->googlePayHandler->pay(
            $this->createMock(SyncPaymentTransactionStruct::class),
            new RequestDataBag([AbstractPaymentMethodHandler::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME => 'paypalOrderId']),
            Generator::createSalesChannelContext()
        );
    }
}
