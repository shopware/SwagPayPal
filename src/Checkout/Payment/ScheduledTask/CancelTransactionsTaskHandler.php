<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Payment\ScheduledTask;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Shopware\Core\System\StateMachine\Exception\StateMachineStateNotFoundException;
use Swag\PayPal\Util\PaymentMethodUtil;

class CancelTransactionsTaskHandler extends ScheduledTaskHandler
{
    private PaymentMethodUtil $paymentMethodUtil;

    private EntityRepositoryInterface $orderTransactionRepo;

    private EntityRepositoryInterface $stateMachineStateRepo;

    private OrderTransactionStateHandler $orderTransactionStateHandler;

    public function __construct(
        EntityRepositoryInterface $scheduledTaskRepository,
        PaymentMethodUtil $paymentMethodUtil,
        EntityRepositoryInterface $stateMachineStateRepository,
        EntityRepositoryInterface $orderTransactionRepository,
        OrderTransactionStateHandler $orderTransactionStateHandler
    ) {
        parent::__construct($scheduledTaskRepository);
        $this->paymentMethodUtil = $paymentMethodUtil;
        $this->stateMachineStateRepo = $stateMachineStateRepository;
        $this->orderTransactionRepo = $orderTransactionRepository;
        $this->orderTransactionStateHandler = $orderTransactionStateHandler;
    }

    public static function getHandledMessages(): iterable
    {
        return [CancelTransactionsTask::class];
    }

    public function run(): void
    {
        $context = Context::createDefaultContext();
        $payPalPaymentMethodId = $this->paymentMethodUtil->getPayPalPaymentMethodId($context);
        if ($payPalPaymentMethodId === null) {
            return;
        }
        $stateMachineStateCriteria = new Criteria();
        $stateMachineStateCriteria->addAssociation('stateMachine');
        $stateMachineStateCriteria->addFilter(
            new EqualsFilter('technicalName', OrderTransactionStates::STATE_IN_PROGRESS)
        );
        $stateMachineStateCriteria->addFilter(
            new EqualsFilter('stateMachine.technicalName', OrderTransactionStates::STATE_MACHINE)
        );
        $stateInProgressId = $this->stateMachineStateRepo->searchIds($stateMachineStateCriteria, $context)->firstId();
        if ($stateInProgressId === null) {
            throw new StateMachineStateNotFoundException(
                OrderTransactionStates::STATE_MACHINE,
                OrderTransactionStates::STATE_IN_PROGRESS
            );
        }

        // PayPal payments which are not confirmed by customers, will be deleted after 24h.
        // Therefore those transactions could safely be cancelled
        $yesterday = new \DateTime('now -1 day');
        $yesterday = $yesterday->setTimezone(new \DateTimeZone('UTC'));

        // Only consider order_transactions which are younger than a week, to ignore older orders once the plugin is updated
        $aWeekAgo = new \DateTime('now -7 day');
        $aWeekAgo = $aWeekAgo->setTimezone(new \DateTimeZone('UTC'));

        $orderTransactionCriteria = new Criteria();
        $orderTransactionCriteria->addFilter(new EqualsFilter('paymentMethodId', $payPalPaymentMethodId));
        $orderTransactionCriteria->addFilter(new EqualsFilter('stateId', $stateInProgressId));
        $orderTransactionCriteria->addFilter(new RangeFilter('createdAt', ['lte' => $yesterday->format(Defaults::STORAGE_DATE_TIME_FORMAT)]));
        $orderTransactionCriteria->addFilter(new RangeFilter('createdAt', ['gte' => $aWeekAgo->format(Defaults::STORAGE_DATE_TIME_FORMAT)]));

        /** @var string[] $orderTransactionIds */
        $orderTransactionIds = $this->orderTransactionRepo->searchIds($orderTransactionCriteria, $context)->getIds();
        foreach ($orderTransactionIds as $orderTransactionId) {
            $this->orderTransactionStateHandler->cancel($orderTransactionId, $context);
        }
    }
}
