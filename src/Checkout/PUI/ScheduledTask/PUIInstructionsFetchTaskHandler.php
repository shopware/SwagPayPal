<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\PUI\ScheduledTask;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\OrFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Swag\PayPal\Checkout\PUI\MessageQueue\PUIInstructionsFetchMessage;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;
use Swag\PayPal\Util\Lifecycle\Method\PUIMethodData;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @internal
 */
#[Package('checkout')]
#[AsMessageHandler(handles: PUIInstructionsFetchTask::class)]
class PUIInstructionsFetchTaskHandler extends ScheduledTaskHandler
{
    public function __construct(
        EntityRepository $scheduledTaskRepository,
        private readonly EntityRepository $orderTransactionRepository,
        private readonly PaymentMethodDataRegistry $methodDataRegistry,
        private readonly MessageBusInterface $bus,
    ) {
        parent::__construct($scheduledTaskRepository);
    }

    public function run(): void
    {
        $date = (new \DateTimeImmutable('now -1h'))->setTimezone(new \DateTimeZone('UTC'));
        $rangeFilter = [
            RangeFilter::GTE => $date->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ];

        $puiHandlerIdentifier = $this->methodDataRegistry->getPaymentMethod(PUIMethodData::class)->getHandler();
        $criteria = (new Criteria())
            ->addFilter(new EqualsFilter('paymentMethod.handlerIdentifier', $puiHandlerIdentifier))
            ->addFilter(new OrFilter([
                new EqualsFilter('stateMachineState.technicalName', OrderTransactionStates::STATE_AUTHORIZED),
                new EqualsFilter('stateMachineState.technicalName', OrderTransactionStates::STATE_IN_PROGRESS),
            ]))
            ->addFilter(new OrFilter([
                new RangeFilter('createdAt', $rangeFilter),
                new RangeFilter('updatedAt', $rangeFilter),
            ]));

        /** @var string[] $transactionIds */
        $transactionIds = $this->orderTransactionRepository->searchIds($criteria, Context::createDefaultContext())->getIds();

        foreach ($transactionIds as $transactionId) {
            $this->bus->dispatch(new PUIInstructionsFetchMessage($transactionId));
        }
    }
}
