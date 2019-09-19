<?php declare(strict_types=1);

namespace Swag\PayPal\Test\Mock\Webhook\Handler;

use Shopware\Core\Framework\Context;
use Swag\PayPal\PayPal\Api\Webhook;
use Swag\PayPal\Test\Mock\Repositories\OrderTransactionRepoMock;
use Swag\PayPal\Webhook\WebhookHandler;

class DummyWebhook implements WebhookHandler
{
    public const EVENT_TYPE = 'PAYPAL_TEST_WEBHOOK';

    public const ORDER_TRANSACTION_UPDATE_DATA_KEY = 'dummyWebhookInvoked';

    private $orderTransactionRepo;

    public function __construct(OrderTransactionRepoMock $orderTransactionRepo)
    {
        $this->orderTransactionRepo = $orderTransactionRepo;
    }

    public function getEventType(): string
    {
        return self::EVENT_TYPE;
    }

    public function invoke(Webhook $webhook, Context $context): void
    {
        $this->orderTransactionRepo->update([[self::ORDER_TRANSACTION_UPDATE_DATA_KEY => true]], $context);
    }
}
