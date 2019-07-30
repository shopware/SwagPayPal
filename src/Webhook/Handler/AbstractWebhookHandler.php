<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Webhook\Handler;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Swag\PayPal\PayPal\Api\Webhook;
use Swag\PayPal\SwagPayPal;
use Swag\PayPal\Webhook\Exception\WebhookOrderTransactionNotFoundException;
use Swag\PayPal\Webhook\WebhookHandler;

abstract class AbstractWebhookHandler implements WebhookHandler
{
    /**
     * @var EntityRepositoryInterface
     */
    protected $orderTransactionRepo;

    /**
     * @var OrderTransactionStateHandler
     */
    protected $orderTransactionStateHandler;

    public function __construct(
        DefinitionInstanceRegistry $definitionRegistry,
        OrderTransactionStateHandler $orderTransactionStateHandler,
        OrderTransactionDefinition $orderTransactionDefinition
    ) {
        $this->orderTransactionRepo = $definitionRegistry->getRepository($orderTransactionDefinition->getEntityName());
        $this->orderTransactionStateHandler = $orderTransactionStateHandler;
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
        $criteria->addFilter(
            new EqualsFilter(
                sprintf('customFields.%s', SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_TRANSACTION_ID),
                $payPalTransactionId
            )
        );
        $result = $this->orderTransactionRepo->search($criteria, $context);

        if ($result->getTotal() === 0) {
            throw new WebhookOrderTransactionNotFoundException($payPalTransactionId, $this->getEventType());
        }

        return $result->getEntities()->first();
    }
}
