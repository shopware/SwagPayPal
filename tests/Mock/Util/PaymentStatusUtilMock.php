<?php declare(strict_types=1);

namespace Swag\PayPal\Test\Mock\Util;

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

        parent::__construct(
            new StateMachineRegistry(
                $entityRepository,
                $entityRepository,
                new EventDispatcherMock(),
                new DefinitionInstanceRegistryMock([], new DIContainerMock())
            ),
            $entityRepository,
            $entityRepository
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
