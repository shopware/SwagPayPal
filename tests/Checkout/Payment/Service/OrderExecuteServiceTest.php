<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\Payment\Service;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use Swag\PayPal\Checkout\Payment\Service\OrderExecuteService;
use Swag\PayPal\OrdersApi\Patch\OrderNumberPatchBuilder;
use Swag\PayPal\RestApi\V2\Api\Order;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\CaptureOrderCapture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\CreateOrderCapture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\GetCapturedOrderCapture;

/**
 * @internal
 */
class OrderExecuteServiceTest extends TestCase
{
    public function testOrderGetOnMissingPayments(): void
    {
        $orderResource = $this->createMock(OrderResource::class);
        $orderExecuteService = new OrderExecuteService(
            $orderResource,
            $this->createMock(OrderTransactionStateHandler::class),
            $this->createMock(OrderNumberPatchBuilder::class),
            new NullLogger(),
        );

        $captureDataWithMissingPayment = CaptureOrderCapture::get();
        $captureDataWithMissingPayment['purchase_units'][0]['payments'] = null;

        $orderResource->expects(static::once())
            ->method('capture')
            ->willReturn((new Order())->assign($captureDataWithMissingPayment));

        $orderResource->expects(static::once())
            ->method('get')
            ->willReturn((new Order())->assign(GetCapturedOrderCapture::get()));

        $orderExecuteService->captureOrAuthorizeOrder(
            Uuid::randomHex(),
            (new Order())->assign(CreateOrderCapture::get()),
            Uuid::randomHex(),
            Context::createDefaultContext(),
            Uuid::randomHex(),
        );
    }
}
