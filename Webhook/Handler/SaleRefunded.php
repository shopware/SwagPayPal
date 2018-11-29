<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Webhook\Handler;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransactionState\OrderTransactionStateDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransactionState\OrderTransactionStateStruct;
use Shopware\Core\Framework\Api\Exception\ResourceNotFoundException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\RepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use SwagPayPal\PayPal\Api\Webhook;
use SwagPayPal\Webhook\Exception\WebhookOrderTransactionNotFoundException;
use SwagPayPal\Webhook\WebhookEventTypes;

class SaleRefunded extends AbstractWebhookHandler
{
    /**
     * @var RepositoryInterface
     */
    private $orderTransactionStateRepo;

    public function __construct(
        RepositoryInterface $orderTransactionRepo,
        RepositoryInterface $orderTransactionStateRepo
    ) {
        $this->orderTransactionStateRepo = $orderTransactionStateRepo;
        parent::__construct($orderTransactionRepo);
    }

    /**
     * {@inheritdoc}
     */
    public function getEventType(): string
    {
        return WebhookEventTypes::PAYMENT_SALE_REFUNDED;
    }

    /**
     * {@inheritdoc}
     *
     * @throws WebhookOrderTransactionNotFoundException
     * @throws ResourceNotFoundException
     */
    public function invoke(Webhook $webhook, Context $context): void
    {
        $orderTransaction = $this->getOrderTransaction($webhook, $context);

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('position', 14));
        $result = $this->orderTransactionStateRepo->search($criteria, $context);
        if ($result->getTotal() === 0) {
            throw new ResourceNotFoundException(OrderTransactionStateDefinition::getEntityName(), ['position' => 14]);
        }

        /** @var OrderTransactionStateStruct $orderTransactionState */
        $orderTransactionState = $result->getEntities()->first();

        $data = [
            'id' => $orderTransaction->getId(),
            'orderTransactionStateId' => $orderTransactionState->getId(),
        ];
        $this->orderTransactionRepo->update([$data], $context);
    }
}
