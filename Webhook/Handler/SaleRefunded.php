<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Webhook\Handler;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransactionState\OrderTransactionStateStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\RepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use SwagPayPal\PayPal\Struct\Webhook;
use SwagPayPal\Webhook\WebhookEventTypes;

class SaleRefunded extends AbstractWebhookHandler
{
    /**
     * @var RepositoryInterface
     */
    private $orderTransactionStateRepo;

    public function __construct(
        LoggerInterface $logger,
        RepositoryInterface $orderTransactionRepo,
        RepositoryInterface $orderTransactionStateRepo
    ) {
        $this->orderTransactionStateRepo = $orderTransactionStateRepo;
        parent::__construct($logger, $orderTransactionRepo);
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
     */
    public function invoke(Webhook $webhook, Context $context): void
    {
        $orderTransaction = $this->getOrderTransaction($webhook, $context);
        if ($orderTransaction === null) {
            return;
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('position', 14));
        $result = $this->orderTransactionStateRepo->search($criteria, $context);
        if ($result->getTotal() === 0) {
            $this->logger->error('[PayPal SaleDenied-Webhook] Could not find order transaction state');

            return;
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
