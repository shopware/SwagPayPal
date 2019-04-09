<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Test\Mock\Webhook\Handler;

use Shopware\Core\Framework\Context;
use SwagPayPal\PayPal\Api\Webhook;
use SwagPayPal\Test\Mock\Repositories\OrderTransactionRepoMock;
use SwagPayPal\Webhook\WebhookHandler;

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
