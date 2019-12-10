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
use Swag\PayPal\Test\Mock\DIContainerMock;
use Swag\PayPal\Test\Mock\EventDispatcherMock;
use Swag\PayPal\Test\Mock\Repositories\DefinitionInstanceRegistryMock;
use Swag\PayPal\Test\Mock\Repositories\EntityRepositoryMock;
use Swag\PayPal\Util\PaymentStatusUtil;
use Symfony\Component\HttpFoundation\Request;

class PaymentStatusUtilMock extends PaymentStatusUtil
{
    public function __construct()
    {
        $entityRepository = new EntityRepositoryMock();
        $stateMachineRegistry = new StateMachineRegistry(
            $entityRepository,
            $entityRepository,
            $entityRepository,
            new EventDispatcherMock(),
            new DefinitionInstanceRegistryMock([], new DIContainerMock())
        );

        parent::__construct(
            $entityRepository,
            new OrderTransactionStateHandler($stateMachineRegistry)
        );
    }

    public function applyVoidStateToOrder(string $orderId, Context $context): void
    {
    }

    public function applyCaptureStateToPayment(string $orderId, Request $request, Context $context): void
    {
    }

    public function applyRefundStateToPayment(string $orderId, Request $request, Context $context): void
    {
    }
}
