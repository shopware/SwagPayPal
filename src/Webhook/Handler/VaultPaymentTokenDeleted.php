<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Webhook\Handler;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Webhook as WebhookV1;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Token;
use Swag\PayPal\RestApi\V2\Api\Webhook as WebhookV2;
use Swag\PayPal\Webhook\Exception\WebhookException;
use Swag\PayPal\Webhook\WebhookEventTypes;

#[Package('checkout')]
class VaultPaymentTokenDeleted extends AbstractWebhookHandler
{
    /**
     * @internal
     */
    public function __construct(
        EntityRepository $orderTransactionRepository,
        OrderTransactionStateHandler $orderTransactionStateHandler,
        private readonly EntityRepository $vaultTokenRepository,
    ) {
        parent::__construct($orderTransactionRepository, $orderTransactionStateHandler);
    }

    public function getEventType(): string
    {
        return WebhookEventTypes::VAULT_PAYMENT_TOKEN_DELETED;
    }

    /**
     * @param WebhookV1|WebhookV2 $webhook
     */
    public function invoke(PayPalApiStruct $webhook, Context $context): void
    {
        if (!$webhook->getResource() instanceof Token) {
            throw new WebhookException($this->getEventType(), 'Given webhook does not have needed resource data');
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('token', $webhook->getResource()->getId()));

        $ids = $this->vaultTokenRepository->searchIds($criteria, $context)->getIds();

        if (!$ids) {
            return;
        }

        $this->vaultTokenRepository->delete(\array_map(fn ($id) => ['id' => $id], $ids), $context);
    }
}
