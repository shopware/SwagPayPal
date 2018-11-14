<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Webhook\Handler;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\RepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use SwagPayPal\PayPal\Struct\Webhook;
use SwagPayPal\Webhook\WebhookHandler;

abstract class AbstractWebhookHandler implements WebhookHandler
{
    /**
     * @var RepositoryInterface
     */
    protected $orderTransactionRepo;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(LoggerInterface $logger, RepositoryInterface $orderTransactionRepo)
    {
        $this->logger = $logger;
        $this->orderTransactionRepo = $orderTransactionRepo;
    }

    abstract public function getEventType(): string;

    abstract public function invoke(Webhook $webhook, Context $context): void;

    protected function getOrderTransaction(Webhook $webhook, Context $context): ?OrderTransactionStruct
    {
        $paypalTransactionId = $webhook->getResource()['parent_payment'];
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('details.swag_paypal.transactionId', $paypalTransactionId));
        $result = $this->orderTransactionRepo->search($criteria, $context);

        if ($result->getTotal() === 0) {
            $this->logger->error(
                sprintf(
                    '[PayPal %s Webhook] Could not find associated order with the PayPal ID "%s"',
                    $this->getEventType(),
                    $paypalTransactionId
                ),
                ['webhook' => $webhook->toArray()]
            );

            return null;
        }

        return $result->getEntities()->first();
    }
}
