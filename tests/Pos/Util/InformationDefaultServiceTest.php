<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Util;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\Exception\PaymentMethodNotAvailableException;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Pos\Payment\PosPayment;

/**
 * @internal
 */
#[Package('checkout')]
class InformationDefaultServiceTest extends TestCase
{
    public function testPaymentMethodNotUsable(): void
    {
        $paymentHandler = new PosPayment($this->createMock(OrderTransactionStateHandler::class));
        $this->expectException(PaymentMethodNotAvailableException::class);
        $paymentHandler->pay(
            new SyncPaymentTransactionStruct(new OrderTransactionEntity(), new OrderEntity()),
            new RequestDataBag(),
            $this->createMock(SalesChannelContext::class)
        );
    }
}
