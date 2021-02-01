<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Helper;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;
use Shopware\Core\System\StateMachine\StateMachineEntity;
use Symfony\Component\DependencyInjection\ContainerInterface;

trait StateMachineStateTrait
{
    protected function getOrderTransactionStateIdByTechnicalName(string $technicalName, ContainerInterface $container, Context $context): ?string
    {
        /** @var EntityRepositoryInterface $stateMachineStateRepo */
        $stateMachineStateRepo = $container->get('state_machine_state.repository');
        $orderTransactionStateMachineId = $this->getOrderTransactionStateMachineId($container, $context);
        if (!$orderTransactionStateMachineId) {
            return null;
        }

        $criteria = new Criteria();
        $criteria->addFilter(
            new MultiFilter(MultiFilter::CONNECTION_AND, [
                new EqualsFilter('stateMachineId', $orderTransactionStateMachineId),
                new EqualsFilter('technicalName', $technicalName),
            ])
        );

        /** @var StateMachineStateEntity|null $stateMachineState */
        $stateMachineState = $stateMachineStateRepo->search($criteria, $context)->first();
        if (!$stateMachineState) {
            return null;
        }

        return $stateMachineState->getId();
    }

    private function getOrderTransactionStateMachineId(ContainerInterface $container, Context $context): ?string
    {
        /** @var EntityRepositoryInterface $stateMachineRepo */
        $stateMachineRepo = $container->get('state_machine.repository');
        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsFilter('technicalName', OrderTransactionStates::STATE_MACHINE)
        );

        /** @var StateMachineEntity|null $orderTransactionStateMachine */
        $orderTransactionStateMachine = $stateMachineRepo->search($criteria, $context)->first();
        if (!$orderTransactionStateMachine) {
            return null;
        }

        return $orderTransactionStateMachine->getId();
    }
}
