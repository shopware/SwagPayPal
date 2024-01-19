<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\Webhook\Handler;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Webhook as WebhookV1;
use Swag\PayPal\RestApi\V2\Api\Webhook as WebhookV2;
use Swag\PayPal\Test\Mock\Repositories\OrderTransactionRepoMock;
use Swag\PayPal\Webhook\WebhookHandler;

/**
 * @internal
 */
#[Package('checkout')]
class DummyWebhook implements WebhookHandler
{
    public const EVENT_TYPE = 'PAYPAL_TEST_WEBHOOK';

    public const ORDER_TRANSACTION_UPDATE_DATA_KEY = 'dummyWebhookInvoked';

    private OrderTransactionRepoMock $orderTransactionRepo;

    public function __construct(OrderTransactionRepoMock $orderTransactionRepo)
    {
        $this->orderTransactionRepo = $orderTransactionRepo;
    }

    public function getEventType(): string
    {
        return self::EVENT_TYPE;
    }

    /**
     * @param WebhookV1|WebhookV2 $webhook
     */
    public function invoke(PayPalApiStruct $webhook, Context $context): void
    {
        $this->orderTransactionRepo->update([[self::ORDER_TRANSACTION_UPDATE_DATA_KEY => true]], $context);
    }
}
