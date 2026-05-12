<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\PUI\MessageQueue;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Order\OrderException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\OrFilter;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Checkout\PUI\Exception\PaymentInstructionsNotReadyException;
use Swag\PayPal\Checkout\PUI\Service\PUIInstructionsFetchService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @internal
 */
#[Package('checkout')]
#[AsMessageHandler]
class PUIInstructionsFetchMessageHandler
{
    public function __construct(
        private readonly EntityRepository $orderTransactionRepository,
        private readonly PUIInstructionsFetchService $instructionsService,
    ) {
    }

    public function __invoke(PUIInstructionsFetchMessage $message): void
    {
        $context = Context::createDefaultContext();

        $criteria = (new Criteria([$message->getTransactionId()]))
            ->setLimit(1)
            ->addAssociation('order')
            ->addFilter(new OrFilter([
                new EqualsFilter('stateMachineState.technicalName', OrderTransactionStates::STATE_AUTHORIZED),
                new EqualsFilter('stateMachineState.technicalName', OrderTransactionStates::STATE_IN_PROGRESS),
            ]));

        /** @var OrderTransactionEntity|null $transaction */
        $transaction = $this->orderTransactionRepository->search($criteria, $context)->first();

        if (!$transaction) {
            return;
        }

        try {
            $this->instructionsService->fetchPUIInstructions(
                $transaction,
                (string) $transaction->getOrder()?->getSalesChannelId(),
                $context,
            );
        } catch (PaymentInstructionsNotReadyException|OrderException) {
        }
    }
}
