<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Webhook\Handler;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use SwagPayPal\PayPal\Api\Webhook;
use SwagPayPal\Webhook\Exception\WebhookOrderTransactionNotFoundException;
use SwagPayPal\Webhook\WebhookHandler;

abstract class AbstractWebhookHandler implements WebhookHandler
{
    /**
     * @var EntityRepositoryInterface
     */
    protected $orderTransactionRepo;

    /**
     * @var StateMachineRegistry
     */
    private $stateMachineRegistry;

    public function __construct(
        DefinitionRegistry $definitionRegistry,
        StateMachineRegistry $stateMachineRegistry
    ) {
        $this->orderTransactionRepo = $definitionRegistry->getRepository(OrderTransactionDefinition::getEntityName());
        $this->stateMachineRegistry = $stateMachineRegistry;
    }

    abstract public function getEventType(): string;

    abstract public function invoke(Webhook $webhook, Context $context): void;

    /**
     * @throws WebhookOrderTransactionNotFoundException
     */
    protected function getOrderTransaction(Webhook $webhook, Context $context): OrderTransactionEntity
    {
        $payPalTransactionId = $webhook->getResource()->getParentPayment();
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('details.swag_paypal.transactionId', $payPalTransactionId));
        $result = $this->orderTransactionRepo->search($criteria, $context);

        if ($result->getTotal() === 0) {
            throw new WebhookOrderTransactionNotFoundException($payPalTransactionId, $this->getEventType());
        }

        return $result->getEntities()->first();
    }

    protected function getStateMachineState(string $technicalStateName, Context $context): string
    {
        return $this->stateMachineRegistry->getStateByTechnicalName(
            Defaults::ORDER_TRANSACTION_STATE_MACHINE,
            $technicalStateName,
            $context
        )->getId();
    }
}
