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
use Swag\PayPal\RestApi\V2\Api\Order;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\Capture;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\Refund;
use Swag\PayPal\Test\Mock\EventDispatcherMock;
use Swag\PayPal\Test\Mock\Repositories\DefinitionInstanceRegistryMock;
use Swag\PayPal\Test\Mock\Repositories\EntityRepositoryMock;
use Swag\PayPal\Util\PaymentStatusUtilV2;
use Swag\PayPal\Util\PriceFormatter;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PaymentStatusUtilV2Mock extends PaymentStatusUtilV2
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

    public function applyRefundState(string $orderTransactionId, Refund $refundResponse, Order $payPalOrder, Context $context): void
    {
    }

    public function applyCaptureState(string $orderTransactionId, Capture $captureResponse, Context $context): void
    {
    }

    public function applyVoidState(string $orderTransactionId, Context $context): void
    {
    }
}
