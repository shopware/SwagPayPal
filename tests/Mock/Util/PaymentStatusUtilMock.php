<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\Util;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Swag\PayPal\RestApi\V1\Api\Capture;
use Swag\PayPal\RestApi\V1\Api\Payment;
use Swag\PayPal\RestApi\V1\Api\Refund;
use Swag\PayPal\Test\Mock\EventDispatcherMock;
use Swag\PayPal\Test\Mock\Repositories\DefinitionInstanceRegistryMock;
use Swag\PayPal\Test\Mock\Repositories\EntityRepositoryMock;
use Swag\PayPal\Util\PaymentStatusUtil;
use Swag\PayPal\Util\PriceFormatter;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PaymentStatusUtilMock extends PaymentStatusUtil
{
    public function __construct(ContainerInterface $container)
    {
        $entityRepository = new EntityRepositoryMock();
        $stateMachineRegistry = new StateMachineRegistry(
            $entityRepository,
            $entityRepository,
            $entityRepository,
            new EventDispatcherMock(),
            new DefinitionInstanceRegistryMock([], $container)
        );

        parent::__construct(
            $entityRepository,
            new OrderTransactionStateHandler($stateMachineRegistry),
            new PriceFormatter()
        );
    }

    public function applyVoidStateToOrder(string $orderId, Context $context): void
    {
    }

    public function applyCaptureState(string $orderId, Capture $captureResponse, Context $context): void
    {
    }

    public function applyRefundStateToPayment(string $orderId, Refund $refundResponse, Context $context): void
    {
    }

    public function applyRefundStateToCapture(string $orderId, Refund $refundResponse, Payment $paymentResponse, Context $context): void
    {
    }
}
