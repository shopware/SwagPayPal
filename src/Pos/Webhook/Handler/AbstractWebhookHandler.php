<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Webhook\Handler;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\Pos\Api\Webhook\Payload\AbstractPayload;
use Swag\PayPal\Pos\Api\Webhook\Webhook;
use Swag\PayPal\Pos\Util\PosSalesChannelTrait;
use Swag\PayPal\Pos\Webhook\WebhookHandler;

#[Package('checkout')]
abstract class AbstractWebhookHandler implements WebhookHandler
{
    use PosSalesChannelTrait;

    /**
     * {@inheritdoc}
     */
    abstract public function getEventName(): string;

    /**
     * @return class-string<AbstractPayload>
     */
    abstract public function getPayloadClass(): string;

    abstract public function execute(AbstractPayload $payload, SalesChannelEntity $salesChannel, Context $context): void;

    /**
     * {@inheritdoc}
     */
    public function invoke(Webhook $webhook, SalesChannelEntity $salesChannel, Context $context): void
    {
        $payloadArray = \json_decode($webhook->getPayload(), true);

        $payloadClass = $this->getPayloadClass();

        $payload = new $payloadClass();
        $payload->assign($payloadArray);

        $this->execute($payload, $salesChannel, $context);
    }
}
