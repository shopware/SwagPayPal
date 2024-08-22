<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Webhook;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\Pos\Api\Webhook\Webhook;

#[Package('checkout')]
interface WebhookHandler
{
    /**
     * Returns the name of the webhook event. Defines which webhook event this handler could handle
     *
     * @see WebhookEventNames
     */
    public function getEventName(): string;

    /**
     * Invokes the webhook using the provided data.
     */
    public function invoke(Webhook $webhook, SalesChannelEntity $salesChannel, Context $context): void;
}
